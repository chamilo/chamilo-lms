<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Annotation;

/**
 * This must be declared on classes which inherit from classes that have
 * requested method invocation securing capabilities.
 *
 * It indicates to the analyzer that the developer is aware of these security
 * restrictions, and has applied them to the root class in an appropriate
 * fashion.
 *
 * We cannot do this automatically without properly analyzing the control flow,
 * and in some cases it is not possible at all. See the following example:
 *
 * <code>
 *     // child class
 *     public function editComment($commentId)
 *     {
 *         // retrieve comment from database
 *         $comment = $this->entityManager->find($commentId);
 *
 *         return parent::editComment($comment);
 *     }
 *
 *     // base class which is inherited from
 *     /**
 *      * @SecureParam(name="comment", permissions="EDIT")
 *      *\/
 *     public function editComment(Comment $comment)
 *     {
 *        // do some supposedly secure action
 *     }
 * <code>
 *
 * The above example can be rewritten so that we can apply security checks
 * automatically:
 *
 * <code>
 *          // child class
 *     public function editComment($commentId)
 *     {
 *         // retrieve comment from database
 *         $comment = $this->entityManager->find($commentId);
 *
 *         return $this->doEditComment($comment);
 *     }
 *
 *     // base class which is inherited from
 *     /**
 *      * @SecureParam(name="comment", permissions="EDIT")
 *      *\/
 *     protected function doEditComment(Comment $comment)
 *     {
 *        // do some secure action
 *     }
 * </code>
 *
 * @Annotation
 * @Target("METHOD")
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class SatisfiesParentSecurityPolicy
{
}