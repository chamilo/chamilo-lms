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

namespace CG\Generator;

/**
 * The default code generation visitor.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DefaultVisitor implements DefaultVisitorInterface
{
    private $writer;

    public function __construct()
    {
        $this->writer = new Writer();
    }

    public function reset()
    {
        $this->writer->reset();
    }

    public function startVisitingClass(PhpClass $class)
    {
        if ($namespace = $class->getNamespace()) {
            $this->writer->write('namespace '.$namespace.';'."\n\n");
        }

        if ($files = $class->getRequiredFiles()) {
            foreach ($files as $file) {
                $this->writer->writeln('require_once '.var_export($file, true).';');
            }

            $this->writer->write("\n");
        }

        if ($useStatements = $class->getUseStatements()) {
            foreach ($useStatements as $alias => $namespace) {
                $this->writer->write('use '.$namespace);

                if (substr($namespace, strrpos($namespace, '\\') + 1) !== $alias) {
                    $this->writer->write(' as '.$alias);
                }

                $this->writer->write(";\n");
            }

            $this->writer->write("\n");
        }

        if ($docblock = $class->getDocblock()) {
            $this->writer->write($docblock);
        }

        $this->writer->write('class '.$class->getShortName());

        if ($parentClassName = $class->getParentClassName()) {
            $this->writer->write(' extends '.('\\' === $parentClassName[0] ? $parentClassName : '\\'.$parentClassName));
        }

        $interfaceNames = $class->getInterfaceNames();
        if (!empty($interfaceNames)) {
            $interfaceNames = array_unique($interfaceNames);

            $interfaceNames = array_map(function($name) {
                if ('\\' === $name[0]) {
                    return $name;
                }

                return '\\'.$name;
            }, $interfaceNames);

            $this->writer->write(' implements '.implode(', ', $interfaceNames));
        }

        $this->writer
            ->write("\n{\n")
            ->indent()
        ;
    }

    public function startVisitingConstants()
    {
    }

    public function visitConstant($name, $value)
    {
        $this->writer->writeln('const '.$name.' = '.var_export($value, true).';');
    }

    public function endVisitingConstants()
    {
        $this->writer->write("\n");
    }

    public function startVisitingProperties()
    {
    }

    public function visitProperty(PhpProperty $property)
    {
        $this->writer->write($property->getVisibility().' '.($property->isStatic()? 'static ' : '').'$'.$property->getName());

        if ($property->hasDefaultValue()) {
            $this->writer->write(' = '.var_export($property->getDefaultValue(), true));
        }

        $this->writer->writeln(';');
    }

    public function endVisitingProperties()
    {
        $this->writer->write("\n");
    }

    public function startVisitingMethods()
    {
    }

    public function visitMethod(PhpMethod $method)
    {
        if ($docblock = $method->getDocblock()) {
            $this->writer->writeln($docblock)->rtrim();
        }

        if ($method->isAbstract()) {
            $this->writer->write('abstract ');
        }

        $this->writer->write($method->getVisibility().' ');

        if ($method->isStatic()) {
            $this->writer->write('static ');
        }

        $this->writer->write('function '.$method->getName().'(');

        $this->writeParameters($method->getParameters());

        if ($method->isAbstract()) {
            $this->writer->write(");\n\n");

            return;
        }

        $this->writer
            ->writeln(")")
            ->writeln('{')
            ->indent()
            ->writeln($method->getBody())
            ->outdent()
            ->rtrim()
            ->write("}\n\n")
        ;
    }

    public function endVisitingMethods()
    {
    }

    public function endVisitingClass(PhpClass $class)
    {
        $this->writer
            ->outdent()
            ->rtrim()
            ->write('}')
        ;
    }

    public function visitFunction(PhpFunction $function)
    {
        if ($namespace = $function->getNamespace()) {
            $this->writer->write("namespace $namespace;\n\n");
        }

        $this->writer->write("function {$function->getName()}(");
        $this->writeParameters($function->getParameters());
        $this->writer
            ->write(")\n{\n")
            ->indent()
            ->writeln($function->getBody())
            ->outdent()
            ->rtrim()
            ->write('}')
        ;
    }

    public function getContent()
    {
        return $this->writer->getContent();
    }

    private function writeParameters(array $parameters)
    {
        $first = true;
        foreach ($parameters as $parameter) {
            if (!$first) {
                $this->writer->write(', ');
            }
            $first = false;

            if ($type = $parameter->getType()) {
                $this->writer->write(
                ('array' === $type ? 'array' : ('\\' === $type[0] ? $type : '\\'. $type))
                .' '
                );
            }

            if ($parameter->isPassedByReference()) {
                $this->writer->write('&');
            }

            $this->writer->write('$'.$parameter->getName());

            if ($parameter->hasDefaultValue()) {
                $this->writer->write(' = '.var_export($parameter->getDefaultValue(), true));
            }
        }
    }
}