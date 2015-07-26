<?php
/**
 * Formatter Class file
 *
 * @author    Chris Yates
 * @copyright Copyright &copy; 2015 BeastBytes - All Rights Reserved
 * @license   BSD 3-Clause
 * @package   Microformats
 */

namespace beastbytes\microformats;

use Yii;
use yii\base\InvalidParamException;
use yii\i18n\NumberFormatter;

/**
 * Extends Yii2 Formatter to provide formatting for microformats.
 */
class Formatter extends \yii\i18n\Formatter
{
    const IS_LATITUDE = true;
    const IS_LONGITUDE = false;
    const MAX_LATITUDE = 90;
    const MAX_LONGITUDE = 180;

    /**
     * @var string the default format string to be used to format an [[asLatitude()|asLatitude()]] coordinate.
     */
    public $coordinateFormat = '%02.6f';
    /**
     * @var array symbold for the parts of a coordinate in the order `degrees`,
     * `minutes`, `seconds`
     */
    public $coordinateSymbols = ['&deg;', '&prime;', '&Prime;'];

    /**
     * @var array text representations of the hemispheres
     */
    public $hemispheres = ['n' => 'N', 'e' => 'E', 's' => 'S', 'w' => 'W'];

    /**
     * Formats the value as a latitude.
     *
     * @param float|int $value the value to be formatted.
     * @param string $format the format used to convert the value into a
     * coordinate string. If null, [[coordinateFormat]] will be used. See [[formatCoordinate]] for details of how to specify the format.
     * @return string the formatted result.
     * @throws InvalidParamException if the input value can not be evaluated as
     * a coordinate value.
     * @throws InvalidConfigException if the coordinate format is invalid.
     * @see coordinateFormat
     * @see formatCoordinate
     */
    public function asLatitude($value, $format = null)
    {
        if (!is_numeric($value) || $value > self::MAX_LATITUDE) {
            throw new InvalidParamException('Invalid latitude "$value"');
        }
        return $this->formatCoordinate($value, $format, self::IS_LATITUDE);
    }

    /**
     * Formats the value as a longitude.
     *
     * @param float|int $value the value to be formatted.
     * @param string $format the format used to convert the value into a
     * coordinate string. If null, [[coordinateFormat]] will be used. See [[formatCoordinate]] for details of how to specify the format.
     * @return string the formatted result.
     * @throws InvalidParamException if the input value can not be evaluated as
     * a coordinate value.
     * @throws InvalidConfigException if the coordinate format is invalid.
     * @see coordinateFormat
     * @see formatCoordinate
     */
    public function asLongitude($value, $format = null)
    {
        if (!is_numeric($value) || $value > self::MAX_LONGITUDE) {
            throw new InvalidParamException('Invalid longitude "$value"');
        }
        return $this->formatCoordinate($value, $format, self::IS_LONGITUDE);
    }

    /**
     * Formats the coordinate.
     *
     * @param float $value the value to be formatted.
     * @param string $format the format used to convert the value into a
     * coordinate string. If null, [[coordinateFormat]] will be used.
     *
     * The format is up to 4 space separated definition groups; there can be 1,
     * 2, or 3 number definitions and a hemisphere definition. The number
     * definitions define the format for degrees, minutes, and seconds; their
     * format is a sprintf number definition. The hemisphere definition is
     * present is "h"; if not present the degrees are signed.
     *
     * Eample formats:
     * - %02.4f - decimal degrees with leading zeros and four decimal places
     * - %02d %02.4f - degrees with leading zeros and decimal minutes with leading zeros
     * and four decimal places
     * - %02d %02d %02.2f h - degrees and minutes with leading zeros, seconds with
     * leading zeros and two decimal places and the hemisphere
     * @return string the formatted result.
     */
    private function formatCoordinate($value, $format, $isLatitude)
    {
        if ($format === null) {
            $format = $this->coordinateFormat;
        }

        $sections = explode(' ', $format);

        // Format the hemisphere
        if (end($sections) === 'h') {
            if ($isLatitude) {
                $h = ' ' . $this->hemispheres[($value < 0 ? 's' : 'n')];
            } else {
                $h = ' ' . $this->hemispheres[($value < 0 ? 'w' : 'e')];
            }
            array_pop($sections);
        } else {
            $h = '';
        }

        $neg = $value < 0;
        $values = [];
        $values[] = abs($value);
        $values[] = ($values[0] - floor($values[0])) * 60;
        $values[] = ($values[0] - floor($values[0])) * 3600 -
        (floor($values[1]) * 60);

        foreach ($values as $i => $value) {
            if (isset($sections[$i])) {
                $values[$i] = sprintf($sections[$i], $value) . $this->coordinateSymbols[$i];
            } else {
                unset($values[$i]);
            }
        }

        return ($h === '' && $neg ? '-' : '') . join(' ', $values) . $h;
    }
}