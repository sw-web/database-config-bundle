<?php

namespace Sw\DatabaseConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

use Sw\DatabaseConfigBundle\Entity\Extension;
use Sw\DatabaseConfigBundle\Form\ConfiguratorType;

/**
 * Configurator Controller
 *
 * @package Sw.DatabaseConfigBundle.Controller
 *
 * @author  Guillaume Petit <guillaume.petit@sword-group.com>
 */
class ConfiguratorController extends Controller
{

    /**
     * Display a form to edit the configuration of a bundle
     *
     * @param Request $request            the request
     * @param string  $configurationClass the bundle name to be configured
     * @param string  $namespace          the namespace of the extension
     *
     * @return Response
     */
    public function editAction(Request $request, $configurationClass, $namespace = '')
    {
        $extensionRepository = $this->getDoctrine()->getRepository('SwDatabaseConfigBundle:Extension');
        $configRepository = $this->getDoctrine()->getRepository('SwDatabaseConfigBundle:Config');

        $manager = $this->getDoctrine()->getManager();

        $tree = $this->get('sw_database_config.services.configuration')->getContainerConfigurationTree($configurationClass);
        $extension = $extensionRepository->findOneBy(
            array(
                'name' => $tree->getName(),
                'namespace' => $namespace,
            )
        );

        if (false == $extension) {
            $extension = new Extension();
            $extension->setName($tree->getName());
            $extension->setNamespace($namespace);
        }

        $form = $this->createForm(new ConfiguratorType(), $extension, array('tree' => $tree));

        if ('POST' == $request->getMethod()) {

            $form->bind($request);

            if ($form->isValid()) {

                $this->get('logger')->info('Updating configuration. - ' . (string) $extension);

                // removing the previous config entries from the database
                $configRepository->deleteByExtension($extension->getId());

                $manager->persist($extension);
                $manager->flush($extension);
            }
        }

        return $this->render(
            'SwDatabaseConfigBundle::edit.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * Check if a tree node is configuration enabled
     *
     * @param NodeInterface $arrayNode a node
     *
     * @return bool
     */
    protected function isConfiguratorEnabledNode(NodeInterface $arrayNode)
    {
        foreach ($arrayNode->getChildren() as $node) {
            if ($node->getAttribute('configurator')) {
                return true;
            } elseif ($node instanceof ArrayNode) {
                return $this->isConfiguratorEnabledNode($node);
            }
        }
    }


}
