<?php

namespace Sw\DatabaseConfigBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @package Sw.DatabaseConfigBundle
 *
 * @author  Guillaume Petit <guillaume.petit@sword-group.com>
 */
class Config
{

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Sw\DatabaseConfigBundle\Entity\Config
     */
    private $parent;

    /**
     * @var \Sw\DatabaseConfigBundle\Entity\Extension
     */
    private $extension;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name the config item name
     *
     * @return Config
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param string $value the config item value
     *
     * @return Config
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Add children
     *
     * @param \Sw\DatabaseConfigBundle\Entity\Config $children the child to add
     *
     * @return Config
     */
    public function addChildren(\Sw\DatabaseConfigBundle\Entity\Config $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Sw\DatabaseConfigBundle\Entity\Config $children the child to remove
     *
     * @return void
     */
    public function removeChildren(\Sw\DatabaseConfigBundle\Entity\Config $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Sw\DatabaseConfigBundle\Entity\Config $parent the parent to set
     *
     * @return Config
     */
    public function setParent(\Sw\DatabaseConfigBundle\Entity\Config $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sw\DatabaseConfigBundle\Entity\Config
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set extension
     *
     * @param \Sw\DatabaseConfigBundle\Entity\Extension $extension the extension to set
     *
     * @return Config
     */
    public function setExtension(\Sw\DatabaseConfigBundle\Entity\Extension $extension = null)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get extension
     *
     * @return \Sw\DatabaseConfigBundle\Entity\Extension
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Return the configuration tree (associative array)
     *
     * @return multitype:array |string
     */
    public function getConfigTree()
    {
        if (count($this->children) > 0) {
            $configArray = array();
            foreach ($this->children as $child) {
                $configArray[$child->getName()] = $child->getConfigTree();
            }

            return $configArray;
        }

        if (is_numeric($this->value)) {
            $this->value = intval($this->value);
        }

        return $this->value;
    }

    /**
     * Get config child by name
     *
     * @param string $configName the config name
     *
     * @return Config|NULL
     */
    public function get($configName)
    {
        foreach ($this->getChildren() as $config) {
            if ($config->getName() == $configName) {
                if ($config->getValue() != '') {
                    return $config->getValue();
                } else {
                    return $config;
                }
            }
        }
        return null;
    }

}
