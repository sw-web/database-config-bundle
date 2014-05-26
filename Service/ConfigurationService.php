<?php
namespace Sw\DatabaseConfigBundle\Service;

use Sw\DatabaseConfigBundle\Entity\ExtensionRepository;
use Sw\DatabaseConfigBundle\Entity\Config;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Config\Definition\BooleanNode;
use Symfony\Component\Config\Definition\IntegerNode;
use Symfony\Component\Config\Definition\FloatNode;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Sw\DatabaseConfigBundle\Entity\ConfigRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Form;
use Sw\DatabaseConfigBundle\Form\ConfiguratorType;

/** ConfigurationService
 *
 * @package DatabaseConfigBundle
 * @author  Guillaume Petit <guillaume.petit@sword-group.com>
 *
 */
class ConfigurationService
{
    /**
     * @var ExtensionRepository the repository to the extension doctrine entity
     */
    private $extensionRepository;

    /**
     * @var ConfigRepository the configuration repository
     */
    private $configRepository;

    /**
     * @var EntityManager the doctrine entity manager
     */
    private $em;

    /**
     * @var FormFactory the symfony form factory
     */
    private $formFactory;

    /**
     * @var Logger the logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ConfigRepository    $configRepository    the configuration repository
     * @param ExtensionRepository $extensionRepository the repository for the extension entity
     * @param EntityManager       $entityManager       the entity manager
     * @param FormFactory         $formFactory         the form factory
     * @param Logger              $logger              the logger
     *
     * @return void
     */
    public function __construct(
        ConfigRepository    $configRepository,
        ExtensionRepository $extensionRepository,
        EntityManager       $entityManager,
        FormFactory         $formFactory,
        Logger              $logger
    ) {
        $this->extensionRepository = $extensionRepository;
        $this->configRepository = $configRepository;
        $this->em = $entityManager;
        $this->formFactory = $formFactory;
        $this->logger = $logger;
    }

     /**
     * Get configuration value either from database or bundle config
     *
     * @param ConfigurationInterface $configuration the configuration
     * @param string                 $namespace     the namespace linked to the extension
     * @param string                 $key           the configuration key
     *
     * @throws \InvalidArgumentException when the configuration key is not found
     * @return mixed string|boolean the configuration value or false if it doesn't exists
     */
    public function getConfigurationValue($configuration, $namespace, $key)
    {
        $value = '';
        $path = explode('.', $key);
        $tree = $configuration->getConfigTreeBuilder()->buildTree();

        $treeName = $tree->getName();
        $node = $this->getConfigurationFromTree($tree, $path);

        if ($node === null) {
            throw new \InvalidArgumentException('Configuration key not found: ' . $key);
        }

        if (null !== $value = $this->getConfigurationFromDatabase($treeName, $namespace, $path)) {
            if ($node instanceof BooleanNode) {
                $value = (boolean) $value;
            } elseif ($node instanceof IntegerNode) {
                $value = (integer) $value;
            } elseif ($node instanceof FloatNode) {
                $value = (float) $value;
            }
        } else {
            $value = $node->getDefaultValue();
        }
        return $value;
    }

    /**
     * Get configuration value from database
     *
     * @param string $extensionName the extension name
     * @param string $namespace     the namespace linked to the extension
     * @param string $path          the configuration path
     *
     * @return string|null
     */
    protected function getConfigurationFromDatabase($extensionName, $namespace, $path)
    {
        $value = null;
        $extension = $this->extensionRepository->findOneBy(
            array(
                'name' => $extensionName,
                'namespace' => $namespace,
            )
        );
        if ($extension) {
            $value = $extension;
            foreach ($path as $pathElement) {
                $value = $value->get($pathElement);
                if ($value == null) {
                    break;
                }
            }
        }
        return $value;
    }

    /**
     * Get configuration value from default bundle configuration
     *
     * @param ArrayNodeDefinition $tree the configuration tree
     * @param string              $path the path of the configuration key
     *
     * @return string|null
     */
    protected function getConfigurationFromTree(ArrayNode $tree, $path)
    {
        foreach ($path as $pathElement) {
            foreach ($tree->getChildren() as $node) {
                if ($node->getName() == $pathElement) {
                    if ($node instanceof ArrayNode) {
                        $tree = $node;
                    } else {
                        return $node;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Generates the configuration form and bind it to a new or already existing extension.
     *
     * @param ConfigurationInterface $configuration the configuration definition interface
     * @param string                 $namespace     the extension namespace
     *
     * @return Form
     */
    public function getConfigurationForm(ConfigurationInterface $configuration, $namespace = '')
    {
        $tree = $configuration->getConfigTreeBuilder()->buildTree();
        $extension = $this->extensionRepository->findOneBy(
            array(
                'name'      => $tree->getName(),
                'namespace' => $namespace,
            )
        );

        if (false == $extension) {
            $extension = new Extension();
            $extension->setName($tree->getName());
            $extension->setNamespace($namespace);
        }

        return $this->formFactory->create(new ConfiguratorType(), $extension, array('tree' => $tree));
    }

    /**
     * Saves the extension
     *
     * @param Form $form the form
     *
     * @return void
     */
    public function saveConfigurationForm(Form $form)
    {
        $extension = $form->getData();

        $this->logger->info('Updating configuration. - ' . (string) $extension);

        // removing the previous config entries from the database
        $this->configRepository->deleteByExtension($extension->getId());

        $this->em->persist($extension);
        $this->em->flush($extension);
    }

}
