<?php

namespace Ip\SorterBundle\Model;

trigger_error('AbstractSort will be removed in the next major version, use BaseSort instead', E_USER_DEPRECATED);

use Ip\SorterBundle\Utils\simpleSorter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class AbstractSort
 * @package Ip\SorterBundle\Model
 * @deprecated
 */
abstract class AbstractSort
{
    /**
     * @return integer
     */
    abstract public function getId();

    /**
     * @return integer
     */
    abstract public function getSort();

    /**
     * @param integer $sort
     */
    abstract public function setSort($sort);

    /**
     * @return array
     */
    public function hasSuperCategory()
    {
        return array();
    }

    /**
     * @param Controller $controller
     */
    public function moveUp(Controller &$controller)
    {
        simpleSorter::moveUp(
            $controller,
            $this
        );
    }

    /**
     * @param Controller $controller
     */
    public function moveDown(Controller &$controller)
    {
        simpleSorter::moveUp(
            $controller,
            $this
        );
    }
}
