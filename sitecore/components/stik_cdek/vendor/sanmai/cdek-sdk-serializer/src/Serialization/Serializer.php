<?php
/**
 * This code is licensed under the MIT License.
 *
 * Copyright (c) 2018 Appwilio (http://appwilio.com), greabock (https://github.com/greabock), JhaoDa (https://github.com/jhaoda)
 * Copyright (c) 2018 Alexey Kopytko <alexey@kopytko.com> and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace CdekSDK\Serialization;

use CdekSDK\Serialization\Exception\DeserializationException;
use CdekSDK\Serialization\Exception\LibXMLError;
use CdekSDK\Serialization\Exception\XmlErrorException;
use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;

final class Serializer implements SerializerInterface
{
    const SERIALIZATION_XML = 'xml';

    private static $addGlobalIgnoredAnnotations = true;

    /** @var SerializerInterface */
    private $serializer;

    private $ctypeEnabled;

    /**
     * @var PreDeserializeEvent
     *
     * @see Serializer::getLastSeenSimpleXMLElement()
     * @see Serializer::deserialize() - ?? ?????????? ?????????????????? ????????????????????
     */
    private $lastEvent;

    public function __construct()
    {
        /** @var SerializerBuilder $builder */
        $builder = SerializerBuilder::create();

        $builder->configureHandlers(function (HandlerRegistryInterface $registry) {
            $registry->registerSubscribingHandler(new NullableDateTimeHandler());
        });

        $builder->setPropertyNamingStrategy(new SerializedNameAnnotationStrategy(new IdenticalPropertyNamingStrategy()));

        /*
         * ?? ???????? ???????????????? ?????????? ???????? ?????? ?? ??????????????, ?????? ?? ?? ???????????? ????????????????. ????????????????, pvzCode="123" ?? PvzCode="123".
         * ?????????? ???? ?????????????????? ?????????????????? ???????????????? ?????? ?????????????? ???????? ?????????????????? (?????? ?? 4f5ca9d27), ???????????????? ??????
         * ???????????????? ?? ?????????????? ????????, ?? ?????????????? ?????????????? ??????????.
         */
        $builder->configureListeners(function (EventDispatcher $dispatcher) {
            /** @psalm-suppress MixedAssignment */
            $dispatcher->addListener(Events::PRE_DESERIALIZE, function (PreDeserializeEvent $event) {
                $data = $event->getData();

                if ($data instanceof \SimpleXMLElement) {
                    $event->setData($this->updateAttributesCase($data));
                }

                $this->lastEvent = $event;
            }, null, 'xml');
        });

        /** @psalm-suppress MixedAssignment */
        $this->serializer = $builder->build();

        // Can be disabled in certain environments (customized PHP build?)
        $this->ctypeEnabled = \function_exists('\ctype_upper');

        // @codeCoverageIgnoreStart
        if (self::$addGlobalIgnoredAnnotations) {
            // Ignore Phan/Psalm issue-suppressing annotations
            AnnotationReader::addGlobalIgnoredName('phan');
            AnnotationReader::addGlobalIgnoredName('psalm');
            AnnotationReader::addGlobalIgnoredName('template');
            // But do that just once
            self::$addGlobalIgnoredAnnotations = false;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @psalm-suppress PossiblyNullIterator
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArgument
     */
    private function updateAttributesCase(\SimpleXMLElement $data): \SimpleXMLElement
    {
        \assert($data->attributes() !== null);

        foreach ($data->attributes() as $attrName => $attrValue) {
            if ($this->ctypeEnabled && \ctype_upper($attrName[0])) {
                continue;
            }

            $data[\ucfirst($attrName)] = $attrValue;
        }

        return $data;
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    private function getLastSeenSimpleXMLElement(): \SimpleXMLElement
    {
        return $this->lastEvent->getData();
    }

    /**
     * @see \JMS\Serializer\SerializerInterface::serialize()
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param mixed $data
     */
    public function serialize($data, string $format, SerializationContext $context = null, string $type = null): string
    {
        return $this->serializer->serialize($data, $format, $context, $type);
    }

    /**
     * @see \JMS\Serializer\SerializerInterface::deserialize()
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function deserialize(string $data, string $type, string $format, DeserializationContext $context = null)
    {
        $data = (string) $data;

        try {
            return $this->serializer->deserialize($data, $type, $format, $context);
        } catch (\JMS\Serializer\Exception\XmlErrorException $e) {
            /**
             * @psalm-suppress MixedArgument
             */
            throw new XmlErrorException(LibXMLError::fromLibXMLError($e->getXmlError(), $data), $e->getCode(), $e);
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            if (self::SERIALIZATION_XML === $format) {
                throw DeserializationException::fromRuntimeException($e, $this->getLastSeenSimpleXMLElement());
            }

            throw $e;
        }
    }
}
