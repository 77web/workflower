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

namespace PHPMentors\Workflower\Process;

use PHPMentors\DomainKata\Service\ServiceInterface;
use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\UnexpectedActivityStateException;
use PHPMentors\Workflower\Workflow\WorkflowRepositoryInterface;

class Process implements ServiceInterface
{
    /**
     * @var int|string
     */
    private $workflowId;

    /**
     * @var WorkflowRepositoryInterface
     */
    private $workflowRepository;

    /**
     * @param int|string                  $workflowId
     * @param WorkflowRepositoryInterface $workflowRepository
     */
    public function __construct($workflowId, WorkflowRepositoryInterface $workflowRepository)
    {
        $this->workflowId = $workflowId;
        $this->workflowRepository = $workflowRepository;
    }

    /**
     * @param EventContextInterface $eventContext
     */
    public function start(EventContextInterface $eventContext)
    {
        assert($eventContext->getProcessContext() !== null);
        assert($eventContext->getProcessContext()->getWorkflow() === null);
        assert($eventContext->getEventId() !== null);

        $eventContext->getProcessContext()->setWorkflow($this->createWorkflow());
        $eventContext->getProcessContext()->getWorkflow()->setProcessData($eventContext->getProcessContext()->getProcessData());
        $eventContext->getProcessContext()->getWorkflow()->start($eventContext->getProcessContext()->getWorkflow()->getFlowObject($eventContext->getEventId()));
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function allocateWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);

        $workItemContext->getProcessContext()->getWorkflow()->allocateWorkItem(
            $workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()),
            $workItemContext->getParticipant()
        );
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function startWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);

        $workItemContext->getProcessContext()->getWorkflow()->startWorkItem(
            $workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()),
            $workItemContext->getParticipant()
        );
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function completeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);

        $workItemContext->getProcessContext()->getWorkflow()->setProcessData($workItemContext->getProcessContext()->getProcessData());
        $workItemContext->getProcessContext()->getWorkflow()->completeWorkItem(
            $workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()),
            $workItemContext->getParticipant()
        );
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     *
     * @throws UnexpectedActivityStateException
     */
    public function executeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow() !== null);
        assert($workItemContext->getActivityId() !== null);
        assert($workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()) instanceof ActivityInterface);

        $activity = $workItemContext->getProcessContext()->getWorkflow()->getFlowObject($workItemContext->getActivityId()); /* @var $activity ActivityInterface */
        if ($activity->isAllocatable()) {
            $this->allocateWorkItem($workItemContext);
            $nextWorkItemContext = new WorkItemContext($workItemContext->getParticipant());
            $nextWorkItemContext->setActivityId($workItemContext->getProcessContext()->getWorkflow()->getCurrentFlowObject()->getId());
            $nextWorkItemContext->setProcessContext($workItemContext->getProcessContext());

            return $this->executeWorkItem($nextWorkItemContext);
        } elseif ($activity->isStartable()) {
            $this->startWorkItem($workItemContext);
            $nextWorkItemContext = new WorkItemContext($workItemContext->getParticipant());
            $nextWorkItemContext->setActivityId($workItemContext->getProcessContext()->getWorkflow()->getCurrentFlowObject()->getId());
            $nextWorkItemContext->setProcessContext($workItemContext->getProcessContext());

            return $this->executeWorkItem($nextWorkItemContext);
        } elseif ($activity->isCompletable()) {
            $this->completeWorkItem($workItemContext);
        } else {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not executable.', $activity->getId()));
        }
    }

    /**
     * @return Workflow
     *
     * @throws WorkflowNotFoundException
     */
    private function createWorkflow()
    {
        $workflow = $this->workflowRepository->findById($this->workflowId);
        if ($workflow === null) {
            throw new WorkflowNotFoundException(sprintf('The workflow "%s" is not found.', $this->workflowId));
        }

        return $workflow;
    }
}
