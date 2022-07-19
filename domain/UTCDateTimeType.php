<?php

declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeImmutableType;

class UTCDateTimeType extends DateTimeImmutableType
{
    /**
     * @var DateTimeZone|null
     */
    private static $utc;

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
//        if ($value instanceof DateTimeImmutable) {
//            $value = $value->setTimezone(self::getUtc());
//        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    private static function getUtc(): DateTimeZone
    {
        return self::$utc ?: self::$utc = new DateTimeZone('UTC');
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof DateTimeImmutable) {
            return $value;
        }
        if (!is_string($value)) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        $converted = DateTimeImmutable::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::getUtc()
        );

        if (!$converted) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $converted;
    }
}