<?php

/*
 * This file is part of the FOSAdvancedEncoderBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\AdvancedEncoderBundle\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * @author Christophe Coevoet <stof@notk.org>
 */
class EncoderFactory implements EncoderFactoryInterface
{
    private $genericFactory;
    private $encoders;

    /**
     * Constructor.
     *
     * @param EncoderFactoryInterface $genericFactory
     * @param array                   $encoders
     */
    public function __construct(EncoderFactoryInterface $genericFactory, array $encoders)
    {
        $this->genericFactory = $genericFactory;
        $this->encoders = $encoders;
    }

    public function getEncoder($user)
    {
        if (!$user instanceof EncoderAwareInterface) {
            return $this->genericFactory->getEncoder($user);
        }

        $encoderName = $user->getEncoderName();

        if (null === $encoderName) {
            return $this->genericFactory->getEncoder($user);
        }

        if (!isset($this->encoders[$encoderName])) {
            throw new \RuntimeException(sprintf('The encoder named "%s" does not exist in the advanced encoder factory. Check your configuration.', $encoderName));
        }

        if (!$this->encoders[$encoderName] instanceof PasswordEncoderInterface) {
            $this->encoders[$encoderName] = $this->createEncoder($this->encoders[$encoderName]);
        }

        return $this->encoders[$encoderName];
    }

    /**
     * Creates an encoder for the given algorithm.
     *
     * @param  array                    $config
     * @return PasswordEncoderInterface
     */
    protected function createEncoder(array $config)
    {
        if (!isset($config['class'])) {
            throw new \InvalidArgumentException(sprintf('"class" must be set in %s.', json_encode($config)));
        }
        if (!isset($config['arguments'])) {
            throw new \InvalidArgumentException(sprintf('"arguments" must be set in %s.', json_encode($config)));
        }

        $reflection = new \ReflectionClass($config['class']);

        return $reflection->newInstanceArgs($config['arguments']);
    }
}
