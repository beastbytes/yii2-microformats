<?php
/**
 * Microformat Class file
 *
 * @author    Chris Yates
 * @copyright Copyright &copy; 2015 BeastBytes - All Rights Reserved
 * @license   BSD 3-Clause
 * @package   Microformats
 */

namespace beastbytes\microformats;

use yii\base\Arrayable;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Html;
use yii\helpers\StringHelper;

/**
 * Microformat widget class.
 * Generate {@link http://microformats.org microformats} whose values are
 * obtained from a model; the model can be either an instance of [[Model]]
 * or an associative array.
 *
 * The [[attributes]] property to determines which microformat properties will
 * be displayed, which model attributes provide the data and how they should be
 * formatted.
 */
class Microformat extends \yii\widgets\DetailView
{
    const ROOT_PREFIX = 'h-';

    /**
     * @var array a list of properties to be displayed in the microformat. Each
     * array element represents the specification for one property.
     *
     * A property can be specified as a string in the format of "property", "property:attribute",
     * "property:attribute:format", "property:attribute:format:label", where "property" is
     * the microformat property name, "attribute" is the model attribute that provides the value of the property, "format" is the format of the property, and "label" is the label to display.
     * The "format" is passed to the [[Formatter::format()]] method to format a
     * property value into a displayable text.
     * Please refer to [[Formatter]] for the supported types. Both "format" and
     * "label" are optional.
     * They will take default values if absent.
     *
     * A property can also be specified in terms of an array with the following
     * elements:
     *
     * - property: string, optional - the microformat property name. If this is
     * not specified the property is not a microformat property; useful for
     * rendering additional information within the microformat.
     * If not set "value" must be.
     * - attribute: string, optional - the model attribute name. If this is not
     * specified it will be generated from the base of the property name,
     * e.g. the h-card property "p-given-name" becomes "given_name".
     * - label: string, optional - the label associated with the attribute. If
     * this is not specified it will be generated from the attribute name.
     * - value: mixed, optional - the value to be displayed. If this is not
     * specified, it will be retrieved from [[model]] using the attribute name
     * by calling [[ArrayHelper::getValue()]]. Note that this value will be
     * formatted into displayable text according to the "format" option.
     * - format: string, optional, default "text" - the type of the value that
     * determines how the value will be formatted into displayable text. Please
     * refer to [[Formatter]] forsupported types.
     * - options: array, optional, default [] - HTML attributes for the property.
     * - template: string|callable, optional, default [[template]] - the
     * template used to render this property. If a string, the tokens `{label}`,
     * `{options}`, `{rawValue}`, and `{value}` will be replaced with the label,
     * options, the raw (unformatted) value and the formatted value of the
     * corresponding attribute.
     * If a callback (e.g. an anonymous function), the signature must be:
     *
     * ~~~
     * function ($attribute, $index, $widget)
     * ~~~
     *
     * where `$attribute` is the specification of the attribute being rendered,
     * `$index` is the zero-based index of the attribute in the [[attributes]]
     * array, and `$widget` is this widget instance.
     * - visible: optional - whether the property is visible. If set to `false`,
     * the property will NOT be displayed. Defaults to `true`
     * - microformat: required for embedded microformats, otherwise must not be
     * set, root class name of the embedded microformat.
     * - model: optional for embedded microformats, otherwise must not be set,
     * defaults to the current model.
     * - class: string, optional for embedded microformats, otherwise must not
     * be set, defaults to this class - the embedded microformat class.
     */
    public $attributes;
    /**
     * @var array $options HTML attributes for the widget.
     * The following special options are recognised:
     *
     * - tag: string, the widget container tag; default "div"
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details of how
     * attributes are rendered.
     */
    public $options = [];
    /**
    * @var string the microformat type, e.g. h-card, h-adr, etc.
    */
    public $microformat;
    /**
     * @var string|callable the template used to render an attribute. If a
     * string, the tokens `{label}`, `{options}`, and `{value}` will be replaced
     * with the label, HTML options, and the value for the corresponding
     * attribute.
     * If a callback (e.g. an anonymous function), the signature must be as follows:
     *
     * ~~~
     * function ($attribute, $index, $widget)
     * ~~~
     *
     * where `$attribute` is the specification of the attribute being rendered,
     * `$index` is the zero-based index of the attribute in the [[attributes]]
     * array, and `$widget` refers to this widget instance.
     *
     * An attribute can use a specific template by setting theattribute template
     * property.
     */
    public $template = '<div {options}><span class="label">{label}</span><span class="value">{value}</span></div>';

    /**
     * Initialises the widget
     */
    public function init()
    {
        if (!isset($this->microformat)) {
            throw new InvalidConfigException('The "microformat" property must be specified.');
        } elseif (strpos($this->microformat, self::ROOT_PREFIX) !== 0) {
            throw new InvalidConfigException(strtr('Invalid root microformat class: "{microformat}".', ['{microformat}' => $this->microformat]));
        }
        parent::init();
    }

    /**
     * Run the widget
     */
    public function run()
    {
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        Html::addCssClass($options, $this->microformat);

        echo Html::beginTag($tag, $options);
        foreach ($this->attributes as $i => $attribute) {
            if (isset($attribute['microformat'])) { // embedded Microformat
                echo $attribute['value'];
            } else {
                if (isset($attribute['property'])) {
                    Html::addCssClass($attribute['options'], $attribute['property']);
                }

                echo $this->renderAttribute($attribute, $i);
            }
        }
        echo Html::endTag($tag);
    }

    /**
     * Normalizes the attribute specifications.
     * @throws InvalidConfigException
     */
    protected function normalizeAttributes()
    {
        if ($this->attributes === null) {
            if ($this->model instanceof Model) {
                $this->attributes = $this->model->attributes();
            } elseif (is_object($this->model)) {
                $this->attributes = $this->model instanceof Arrayable ? $this->model->toArray() : array_keys(get_object_vars($this->model));
            } elseif (is_array($this->model)) {
                $this->attributes = array_keys($this->model);
            } else {
                throw new InvalidConfigException('The "model" property must be either an array or an object.');
            }
            sort($this->attributes);
        }

        foreach ($this->attributes as $i => $attribute) {
            if (is_string($attribute)) {
                if (!preg_match('/^([a-z]+(-[a-z]+)+)(:(\w+(\.\w+)?))?(:(\w+))?(:(.*))?$/', $attribute, $matches)) {
                    throw new InvalidConfigException('The attribute must be specified in the format of "property", "property:attribute", "property:attribute:format" or "property:attribute:format:label"');
                }
                $attribute = [
                    'property' => $matches[1],
                    'attribute' => (isset($matches[4])
                        ? $matches[4]
                        : $this->property2Attribute($matches[1])
                    ),
                    'format' => isset($matches[7]) ? $matches[7] : 'text',
                    'label' => isset($matches[9]) ? $matches[9] : null,
                ];
            }

            if (!is_array($attribute)) {
                throw new InvalidConfigException('The attribute configuration must be an array.');
            }

            if (isset($attribute['visible']) && !$attribute['visible']) {
                unset($this->attributes[$i]);
                continue;
            }

            if (isset($attribute['microformat'])) { // embedded Microformat
                if (empty($attribute['model'])) {
                    $attribute['model'] = $this->model;
                }
                $property = $attribute['property'];
                unset($attribute['property']);
                Html::addCssClass($attribute['options'], $property);

                $class = ArrayHelper::remove($attribute, 'class', __CLASS__);
                $attribute['value'] = $class::widget($attribute);
                unset($attribute['label']);
            } else {
                if (isset($attribute['class'])) {
                    throw new InvalidConfigException('"class" must only be specified for embedded microformats');
                }

                if (!isset($attribute['format'])) {
                    $attribute['format'] = 'text';
                }

                if (isset($attribute['property']) && !isset($attribute['attribute'])) {
                    $attribute['attribute'] = $this->property2Attribute(
                        $attribute['property']
                    );
                }

                if (isset($attribute['attribute'])) {
                    $attributeName = $attribute['attribute'];
                    if (!isset($attribute['label'])) {
                        $attribute['label'] = $this->model instanceof Model ? $this->model->getAttributeLabel($attributeName) : Inflector::camel2words($attributeName, true);
                    }
                    if (!array_key_exists('value', $attribute)) {
                        $attribute['value'] = ArrayHelper::getValue($this->model, $attributeName);
                    }
                } elseif (!isset($attribute['value'])) {
                    throw new InvalidConfigException('The attribute configuration requires the "attribute" or "property" element to determine the value and display the label.');
                }
            }

            $this->attributes[$i] = $attribute;
        }
    }

    /**
     * Renders a single attribute.
     * @param array $attribute the specification of the attribute to be rendered.
     * @param integer $index the zero-based index of the attribute in the [[attributes]] array
     * @return string the rendering result
     */
    protected function renderAttribute($attribute, $index)
    {
        if ($attribute['value'] === '') {
            return '';
        }

        $template = ArrayHelper::getValue($attribute, 'template', $this->template);

        if (is_string($template)) {
            if (isset($attribute['label'])) {
                return strtr($template, [
                    '{label}' => $attribute['label'],
                    '{options}' => Html::renderTagAttributes($attribute['options']),
                    '{rawValue}' => $attribute['value'],
                    '{value}' => $this->formatter->format(
                        $attribute['value'],
                        $attribute['format']
                    )
                ]);
            } else {
                return strtr($template, [
                    '{value}' => $this->formatter->format(
                        $attribute['value'],
                        $attribute['format']
                    )
                ]);
            }
        } else {
            return call_user_func($template, $attribute, $index, $this);
        }
    }

    /**
     * Returns the attribute corresponding to the property.
     *
     * @param sring $property the property name
     * @return string the attribute name
     */
    private function property2Attribute($property)
    {
        return str_replace('-', '_', substr($property, strpos($property, '-') + 1));
    }
} // END class Widget extends \yii\base\Widget
