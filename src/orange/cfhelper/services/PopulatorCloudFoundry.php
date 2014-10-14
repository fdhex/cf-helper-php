<?php
/**
 * Copyright (C) 2014 Orange
 *
 * This software is distributed under the terms and conditions of the 'MIT' license which can be
 * found in the file 'LICENSE' in this package distribution or at 'http://opensource.org/licenses/MIT'.
 *
 * Author: Arthur Halet
 * Date: 01-07-2014
 */
namespace orange\cfhelper\services;

use orange\cfhelper\application\ApplicationInfo;

/**
 * Class PopulatorCloudFoundry
 * @package orange\cfhelper\services
 */
class PopulatorCloudFoundry extends Populator
{
    /**
     * @var array
     */
    private $vcapServices;
    /**
     * @var array(string => Service)
     */
    private $services = array();
    /**
     * @var ApplicationInfo
     */
    private $applicationInfo;

    /**
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->vcapServices = json_decode($_ENV['VCAP_SERVICES'], true);
        if (empty($this->vcapServices)) {
            $this->vcapServices = array();
        }
    }

    /**
     * @param $name
     * @return null|Service
     * @throws \Exception
     */
    public function getService($name)
    {
        if (!empty($this->services[$name])) {
            return $this->services[$name];
        }
        $service = $this->getServiceFirst($name);
        if (!empty($service)) {
            return $service;
        }
        $service = $this->getServiceInside($name);
        if (!empty($service)) {
            return $service;
        }
        throw new \Exception("Service $name cannot be found.");
    }

    /**
     * @param $name
     * @return null|Service
     */
    private function getServiceFirst($name)
    {
        foreach ($this->vcapServices as $serviceName => $service) {
            if (preg_match('#^' . $name . '$#i', $serviceName)) {
                return $this->makeService($service[0]);
            }
        }
        return null;
    }

    /**
     * @param $service
     * @return Service
     */
    private function makeService($service)
    {
        $serviceObject = new Service($service['name'], $service['credentials'], $service['label']);
        unset($service['name']);
        unset($service['credentials']);
        unset($service['label']);
        $serviceObject->addDatas($service);
        $this->services[$serviceObject->getName()] = $serviceObject;
        return $serviceObject;
    }

    /**
     * @param $name
     * @return null|Service
     */
    private function getServiceInside($name)
    {
        foreach ($this->vcapServices as $serviceFirstName => $services) {
            foreach ($services as $service) {
                if (preg_match('#^' . $name . '$#i', $service['name'])) {
                    return $this->makeService($service);
                }
            }
        }
        return null;
    }

    /**
     * @return ApplicationInfo
     */
    public function getApplicationInfo()
    {
        return $this->applicationInfo;
    }

    /**
     * @Required
     * @param ApplicationInfo $applicationInfo
     *
     */
    public function setApplicationInfo(ApplicationInfo $applicationInfo)
    {
        $this->applicationInfo = $applicationInfo;
        $this->populateApplicationInfo();
    }

    /**
     *
     */
    public function populateApplicationInfo()
    {
        $vcapApplication = json_decode($_ENV['VCAP_APPLICATION'], true);
        if (empty($vcapApplication)) {
            return;
        }
        foreach ($vcapApplication as $key => $value) {
            if (is_array($value)) {
                $this->applicationInfo->$key = (object)$value;
            } else {
                $this->applicationInfo->$key = $value;
            }
        }
    }

}