<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema\Enums;

/**
 * Enum representing all Laravel column types.
 */
enum ColumnType: string
{
    // Integer Types
    case BigInteger = 'bigInteger';
    case Integer = 'integer';
    case MediumInteger = 'mediumInteger';
    case SmallInteger = 'smallInteger';
    case TinyInteger = 'tinyInteger';

    // Unsigned Integer Types
    case UnsignedBigInteger = 'unsignedBigInteger';
    case UnsignedInteger = 'unsignedInteger';
    case UnsignedMediumInteger = 'unsignedMediumInteger';
    case UnsignedSmallInteger = 'unsignedSmallInteger';
    case UnsignedTinyInteger = 'unsignedTinyInteger';

    // Auto-Incrementing Types
    case Id = 'id';
    case BigIncrements = 'bigIncrements';
    case Increments = 'increments';
    case MediumIncrements = 'mediumIncrements';
    case SmallIncrements = 'smallIncrements';
    case TinyIncrements = 'tinyIncrements';

    // Decimal/Float Types
    case Decimal = 'decimal';
    case Double = 'double';
    case Float = 'float';

    // String Types
    case Char = 'char';
    case String = 'string';
    case Text = 'text';
    case MediumText = 'mediumText';
    case LongText = 'longText';
    case TinyText = 'tinyText';

    // Binary Types
    case Binary = 'binary';

    // Boolean
    case Boolean = 'boolean';

    // Date/Time Types
    case Date = 'date';
    case DateTime = 'dateTime';
    case DateTimeTz = 'dateTimeTz';
    case Time = 'time';
    case TimeTz = 'timeTz';
    case Timestamp = 'timestamp';
    case TimestampTz = 'timestampTz';
    case Year = 'year';

    // JSON Types
    case Json = 'json';
    case Jsonb = 'jsonb';

    // UUID/ULID Types
    case Uuid = 'uuid';
    case Ulid = 'ulid';
    case ForeignUuid = 'foreignUuid';
    case ForeignUlid = 'foreignUlid';

    // Foreign Key Types
    case ForeignId = 'foreignId';
    case ForeignIdFor = 'foreignIdFor';

    // Enum/Set Types
    case Enum = 'enum';
    case Set = 'set';

    // Special Types
    case IpAddress = 'ipAddress';
    case MacAddress = 'macAddress';
    case Morphs = 'morphs';
    case NullableMorphs = 'nullableMorphs';
    case UuidMorphs = 'uuidMorphs';
    case NullableUuidMorphs = 'nullableUuidMorphs';
    case UlidMorphs = 'ulidMorphs';
    case NullableUlidMorphs = 'nullableUlidMorphs';
    case RememberToken = 'rememberToken';
    case SoftDeletes = 'softDeletes';
    case SoftDeletesTz = 'softDeletesTz';
    case Timestamps = 'timestamps';
    case TimestampsTz = 'timestampsTz';
    case NullableTimestamps = 'nullableTimestamps';

    // Spatial Types
    case Geometry = 'geometry';
    case Geography = 'geography';
    case Point = 'point';
    case LineString = 'lineString';
    case Polygon = 'polygon';
    case GeometryCollection = 'geometryCollection';
    case MultiPoint = 'multiPoint';
    case MultiLineString = 'multiLineString';
    case MultiPolygon = 'multiPolygon';
    case MultiPolygonZ = 'multiPolygonZ';

    // Vector Type (Laravel 11+)
    case Vector = 'vector';

    // Unknown (for database types that don't map to Laravel)
    case Unknown = 'unknown';

    /**
     * Check if this type is an integer type.
     */
    public function isInteger(): bool
    {
        return in_array($this, [
            self::BigInteger,
            self::Integer,
            self::MediumInteger,
            self::SmallInteger,
            self::TinyInteger,
            self::UnsignedBigInteger,
            self::UnsignedInteger,
            self::UnsignedMediumInteger,
            self::UnsignedSmallInteger,
            self::UnsignedTinyInteger,
            self::Id,
            self::BigIncrements,
            self::Increments,
            self::MediumIncrements,
            self::SmallIncrements,
            self::TinyIncrements,
        ], true);
    }

    /**
     * Check if this type is a string type.
     */
    public function isString(): bool
    {
        return in_array($this, [
            self::Char,
            self::String,
            self::Text,
            self::MediumText,
            self::LongText,
            self::TinyText,
        ], true);
    }

    /**
     * Check if this type is a date/time type.
     */
    public function isDateTime(): bool
    {
        return in_array($this, [
            self::Date,
            self::DateTime,
            self::DateTimeTz,
            self::Time,
            self::TimeTz,
            self::Timestamp,
            self::TimestampTz,
            self::Year,
        ], true);
    }

    /**
     * Check if this type is a numeric type.
     */
    public function isNumeric(): bool
    {
        return $this->isInteger() || in_array($this, [
            self::Decimal,
            self::Double,
            self::Float,
        ], true);
    }

    /**
     * Check if this type is unsigned.
     */
    public function isUnsigned(): bool
    {
        return in_array($this, [
            self::UnsignedBigInteger,
            self::UnsignedInteger,
            self::UnsignedMediumInteger,
            self::UnsignedSmallInteger,
            self::UnsignedTinyInteger,
            self::Id,
            self::BigIncrements,
            self::Increments,
            self::MediumIncrements,
            self::SmallIncrements,
            self::TinyIncrements,
            self::ForeignId,
        ], true);
    }

    /**
     * Check if this type is auto-incrementing.
     */
    public function isAutoIncrement(): bool
    {
        return in_array($this, [
            self::Id,
            self::BigIncrements,
            self::Increments,
            self::MediumIncrements,
            self::SmallIncrements,
            self::TinyIncrements,
        ], true);
    }

    /**
     * Check if this type is a spatial type.
     */
    public function isSpatial(): bool
    {
        return in_array($this, [
            self::Geometry,
            self::Geography,
            self::Point,
            self::LineString,
            self::Polygon,
            self::GeometryCollection,
            self::MultiPoint,
            self::MultiLineString,
            self::MultiPolygon,
            self::MultiPolygonZ,
        ], true);
    }

    /**
     * Try to create a ColumnType from a string value.
     */
    public static function tryFromString(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }
}
