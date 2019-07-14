<?php

namespace Plugin\SimpleNemPay\Entity;

class NemInfo extends \Eccube\Entity\AbstractEntity
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $setting_data;

    /**
     * @var Payment
     */
    private $Payment;

    /**
     * @var integer
     */
    private $del_flg;

    /**
     * @var \DateTime
     */
    private $create_date;

    /**
     * @var \DateTime
     */
    private $update_date;


    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set setting_data
     *
     * @param  string $settingData
     * @return Module
     */
    public function setSettingData($settingData)
    {
        $this->setting_data = $settingData;

        return $this;
    }

    /**
     * Get setting_data
     *
     * @return string
     */
    public function getSettingData()
    {
        return $this->setting_data;
    }

    /**
     * Set Payment
     *
     * @param  Payment
     * @return Module
     */
    public function setPayment(\Eccube\Entity\Payment $Payment)
    {
        $this->Payment = $Payment;

        return $this;
    }

    /**
     * Get Payment
     *
     * @return Payment
     */
    public function getPayment()
    {
        return $this->Payment;
    }

    /**
     * Set del_flg
     *
     * @param  integer $delFlg
     * @return Module
     */
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    /**
     * Get del_flg
     *
     * @return integer
     */
    public function getDelFlg()
    {
        return $this->del_flg;
    }

    /**
     * Set create_date
     *
     * @param  \DateTime $createDate
     * @return Module
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param  \DateTime $updateDate
     * @return Module
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

}
