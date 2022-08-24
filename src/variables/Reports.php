<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\variables;

use Craft;
use craft\helpers\DateTimeHelper;
use yii\base\Behavior;
use DateInterval;

class Reports
{
    public function configure($query, $attribute, $options)
    {
        $criteria = [];
        $startDate = null;
        $endDate = null;
        
        $today = DateTimeHelper::toDateTime(strtotime('today'));
        $today->setTime(0,0,0);
        
        switch ($options['dateRange']['type'])
        {
            case 'custom':
                if (!empty($options['dateRange']['startDate'])) {
                    $startDate = DateTimeHelper::toDateTime($options['dateRange']['startDate']);
                }
                if (!empty($options['dateRange']['endDate'])) {
                    $endDate = DateTimeHelper::toDateTime($options['dateRange']['endDate']);
                    $endDate->setTime(0,0,0)->add(new DateInterval('P1D'));
                }
                break;
                
            case 'Today':
                $startDate = $today;
                $endDate = (clone $startDate)->add(new DateInterval('P1D'));
                break;
                
            case 'This Week':
                $startDate = DateTimeHelper::toDateTime(strtotime('last monday', strtotime('tomorrow')));
                $endDate = (clone $startDate)->add(new DateInterval('P1W'));
                break;
            
            case 'This Month':
                $startDate = DateTimeHelper::toDateTime(strtotime('first day of this month'))->setTime(0,0,0);
                $endDate = (clone $startDate)->add(new DateInterval('P1M'));
                break;
                
            case 'This Year':
                $startDate = DateTimeHelper::toDateTime(strtotime('first day of January'));
                $endDate = (clone $startDate)->add(new DateInterval('P1Y'));
                break;
                
            case 'Past 7 Days':
                $endDate = $today;
                $startDate = (clone $endDate)->sub(new DateInterval('P7D'));
                break;
                
            case 'Past 30 Days':
                $endDate = $today;
                $startDate = (clone $endDate)->sub(new DateInterval('P30D'));
                break;
                
            case 'Past 90 Days':
                $endDate = $today;
                $startDate = (clone $endDate)->sub(new DateInterval('P90D'));
                break;
                
            case 'Past Year':
                $endDate = $today;
                $startDate = (clone $endDate)->sub(new DateInterval('P1Y'));
                break;
                
            case 'This Financial Year':
                $startDate = DateTimeHelper::toDateTime(strtotime('first day of April'));
                $endDate = (clone $startDate)->add(new DateInterval('P1Y'));
                break;
        }
        
        if ($startDate && $endDate) {
            $criteria[$attribute] = ['and', '>= '.DateTimeHelper::toIso8601($startDate), '< '.DateTimeHelper::toIso8601($endDate)];
        } elseif ($startDate) {
            $criteria[$attribute] = '>= '.DateTimeHelper::toIso8601($startDate);
        } elseif ($endDate) {
            $criteria[$attribute] = '< '.DateTimeHelper::toIso8601($endDate);
        }
        
        Craft::configure($query, $criteria);
        
        return $query;
    }
}