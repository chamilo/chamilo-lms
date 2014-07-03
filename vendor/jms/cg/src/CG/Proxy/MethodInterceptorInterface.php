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

namespace CG\Proxy;

/**
 * Interface for Method Interceptors.
 *
 * Implementations of this interface can execute custom code before, and after the
 * invocation of the actual method. In addition, they can also catch, or throw
 * exceptions, modify the return value, or modify the arguments.
 *
 * This is also known as around advice in AOP terminology.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface MethodInterceptorInterface
{
    /**
     * Called when intercepting a method call.
     *
     * @param MethodInvocation $invocation
     * @return mixed the return value for the method invocation
     * @throws \Exception may throw any exception
     */
    function intercept(MethodInvocation $invocation);
}