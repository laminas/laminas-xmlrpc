<?php

namespace Laminas\XmlRpc\Value;

use Laminas\XmlRpc\Exception;

use function date;
use function is_numeric;

class DateTime extends AbstractScalar
{
    /**
     * PHP compatible format string for XML/RPC datetime values
     *
     * @var string
     */
    protected $phpFormatString = 'Ymd\\TH:i:s';

    /**
     * ISO compatible format string for XML/RPC datetime values
     *
     * @var string
     */
    protected $isoFormatString = 'yyyyMMddTHH:mm:ss';

    /**
     * Set the value of a dateTime.iso8601 native type
     *
     * The value is in iso8601 format, minus any timezone information or dashes
     *
     * @param integer|string|\DateTime $value Integer of the unix timestamp or any string that can be parsed
     *                     to a unix timestamp using the PHP strtotime() function
     * @throws Exception\ValueException If unable to create a DateTime object from $value.
     */
    public function __construct($value)
    {
        $this->type = self::XMLRPC_TYPE_DATETIME;

        if ($value instanceof \DateTime) {
            $this->value = $value->format($this->phpFormatString);
        } elseif (is_numeric($value)) { // The value is numeric, we make sure it is an integer
            $this->value = date($this->phpFormatString, (int) $value);
        } else {
            try {
                $dateTime = new \DateTime($value);
            } catch (\Exception $e) {
                throw new Exception\ValueException($e->getMessage(), $e->getCode(), $e);
            }

            $this->value = $dateTime->format($this->phpFormatString); // Convert the DateTime to iso8601 format
        }
    }

    /**
     * Return the value of this object as iso8601 dateTime value
     *
     * @return string Formatted datetime
     */
    public function getValue()
    {
        return $this->value;
    }
}
