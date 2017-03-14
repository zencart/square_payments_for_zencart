<?php
/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace SquareConnect\Model;

use \ArrayAccess;
/**
 * TenderCashDetails Class Doc Comment
 *
 * @category Class
 * @package  SquareConnect
 * @author   Square Inc.
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link     https://squareup.com/developers
 */
class TenderCashDetails implements ArrayAccess
{
    /**
      * Array of property to type mappings. Used for (de)serialization 
      * @var string[]
      */
    static $swaggerTypes = array(
        'buyer_tendered_money' => '\SquareConnect\Model\Money',
        'change_back_money' => '\SquareConnect\Model\Money'
    );
  
    /** 
      * Array of attributes where the key is the local name, and the value is the original name
      * @var string[] 
      */
    static $attributeMap = array(
        'buyer_tendered_money' => 'buyer_tendered_money',
        'change_back_money' => 'change_back_money'
    );
  
    /**
      * Array of attributes to setter functions (for deserialization of responses)
      * @var string[]
      */
    static $setters = array(
        'buyer_tendered_money' => 'setBuyerTenderedMoney',
        'change_back_money' => 'setChangeBackMoney'
    );
  
    /**
      * Array of attributes to getter functions (for serialization of requests)
      * @var string[]
      */
    static $getters = array(
        'buyer_tendered_money' => 'getBuyerTenderedMoney',
        'change_back_money' => 'getChangeBackMoney'
    );
  
    /**
      * $buyer_tendered_money The total amount of cash provided by the buyer, before change is given.
      * @var \SquareConnect\Model\Money
      */
    protected $buyer_tendered_money;
    /**
      * $change_back_money The amount of change returned to the buyer.
      * @var \SquareConnect\Model\Money
      */
    protected $change_back_money;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null)
    {
        if ($data != null) {
            $this->buyer_tendered_money = $data["buyer_tendered_money"];
            $this->change_back_money = $data["change_back_money"];
        }
    }
    /**
     * Gets buyer_tendered_money
     * @return \SquareConnect\Model\Money
     */
    public function getBuyerTenderedMoney()
    {
        return $this->buyer_tendered_money;
    }
  
    /**
     * Sets buyer_tendered_money
     * @param \SquareConnect\Model\Money $buyer_tendered_money The total amount of cash provided by the buyer, before change is given.
     * @return $this
     */
    public function setBuyerTenderedMoney($buyer_tendered_money)
    {
        $this->buyer_tendered_money = $buyer_tendered_money;
        return $this;
    }
    /**
     * Gets change_back_money
     * @return \SquareConnect\Model\Money
     */
    public function getChangeBackMoney()
    {
        return $this->change_back_money;
    }
  
    /**
     * Sets change_back_money
     * @param \SquareConnect\Model\Money $change_back_money The amount of change returned to the buyer.
     * @return $this
     */
    public function setChangeBackMoney($change_back_money)
    {
        $this->change_back_money = $change_back_money;
        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset 
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }
  
    /**
     * Gets offset.
     * @param  integer $offset Offset 
     * @return mixed 
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }
  
    /**
     * Sets value based on offset.
     * @param  integer $offset Offset 
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }
  
    /**
     * Unsets offset.
     * @param  integer $offset Offset 
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
  
    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode(\SquareConnect\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        } else {
            return json_encode(\SquareConnect\ObjectSerializer::sanitizeForSerialization($this));
        }
    }
}
