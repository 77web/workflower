<?php
/*
 * Copyright (c) 2015 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Connection;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Element\ConnectingObjectInterface;
use PHPMentors\Workflower\Workflow\Element\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;
use Symfony\Component\ExpressionLanguage\Expression;

class SequenceFlow implements ConnectingObjectInterface, \Serializable
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var TransitionalInterface
     */
    private $source;

    /**
     * @var FlowObjectInterface
     */
    private $destination;

    /**
     * @var Expression
     */
    private $condition;

    /**
     * @param int|string
     * @param TransitionalInterface $source
     * @param FlowObjectInterface   $destination
     * @param string                $name
     * @param Expression            $condition
     */
    public function __construct($id, TransitionalInterface $source, FlowObjectInterface $destination, $name = null, Expression $condition = null)
    {
        $this->id = $id;
        $this->source = $source;
        $this->destination = $destination;
        $this->name = $name;
        $this->condition = $condition;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'id' => $this->id,
            'name' => $this->name,
            'source' => $this->source,
            'destination' => $this->destination,
            'condition' => $this->condition,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return Expression
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * {@inheritDoc}
     */
    public function equals(EntityInterface $target)
    {
        if (!($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }
}
