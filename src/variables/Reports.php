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
use craft\helpers\Json;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use yii\base\Behavior;
use DateInterval;

class Reports
{
    public function configure(mixed $query, string $attribute, array $options): mixed
    {
        $criteria = [];
        
        $range = $this->_dateRange($options);
        
        if ($range['start'] && $range['end']) {
            $criteria[$attribute] = ['and', '>= '.DateTimeHelper::toIso8601($range['start']), '< '.DateTimeHelper::toIso8601($range['end'])];
        } elseif ($range['start']) {
            $criteria[$attribute] = '>= '.DateTimeHelper::toIso8601($range['start']);
        } elseif ($range['end']) {
            $criteria[$attribute] = '< '.DateTimeHelper::toIso8601($range['end']);
        }
        
        Craft::configure($query, $criteria);
        
        return $query;
    }
    
    public function getDateRange(string $attribute, array $options): mixed
    {
        $range = $this->_dateRange($options);
        
        if ($range['start'] && $range['end']) {
            return ['and', $attribute.' >= "'.$range['start']->format('Y-m-d\T00:00:00').'"', $attribute.' < "'.$range['end']->format('Y-m-d\T00:00:00').'"'];
        } elseif ($range['start']) {
            return $attribute.' >= "'.$range['start']->format('Y-m-d\T00:00:00').'"';
        } elseif ($range['end']) {
            return $attribute.' < "'.$range['end']->format('Y-m-d\T00:00:00').'"';
        }
        
        return null;
    }
    
    public function update($id, $data = null): void
    {
        $table = Craft::$app->db->quoteTableName('{{%dnareports}}');
        
        if ($data == null) {
            Craft::$app->db->createCommand('UPDATE '.$table.' SET `data` = "" WHERE id = '.$id)->execute();
        } else {
            $data = StringHelper::replaceAll($data, ["\n","\t","\r"], ['','','']);
            $data = Json::encode($data);
            
            Craft::$app->db->createCommand('UPDATE '.$table.' SET `data` = CONCAT(data, '.$data.') WHERE id = '.$id)->execute();
        }
    }
    
    public function checkDateInRange($date, array $options): bool
    {
        $range = $this->_dateRange($options);
        
        $date = DateTimeHelper::toDateTime($date);
            
        if ($range['start'] && $range['end']) {
            return $date >= $range['start'] && $date < $range['end'];
        } elseif ($range['start']) {
            return $date >= $range['start'];
        } elseif ($range['end']) {
            return $date < $range['end'];
        }
        
        return false;
    }
    
    
    private function _dateRange(array $options): array
    {
        $startDate = null;
        $endDate = null;
        
        $today = DateTimeHelper::toDateTime(strtotime('today'));
        $today->setTime(0,0,0);
        
        switch ($options['dateRange']['type'])
        {
            case 'Custom':
                if (!empty($options['dateRange']['startDate'])) {
                    $startDate = DateTimeHelper::toDateTime($options['dateRange']['startDate'])->setTime(0,0,0);
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
                
            case 'Yesterday':
                $startDate = (clone $today)->sub(new DateInterval('P1D'));
                $endDate = $today;
                break;
                
            case 'This Week':
                $startDate = DateTimeHelper::toDateTime(strtotime('last monday', strtotime('tomorrow')))->setTime(0,0,0);
                $endDate = (clone $startDate)->add(new DateInterval('P1W'));
                break;
                
            case 'Last Week':
                $startDate = DateTimeHelper::toDateTime(strtotime('last monday', strtotime('tomorrow')))->setTime(0,0,0)->sub(new DateInterval('P1W'));
                $endDate = (clone $startDate)->add(new DateInterval('P1W'));
                break;
            
            case 'This Month':
                $startDate = DateTimeHelper::toDateTime(strtotime('first day of this month'))->setTime(0,0,0);
                $endDate = (clone $startDate)->add(new DateInterval('P1M'));
                break;
                
            case 'Last Month':
                $startDate = (DateTimeHelper::toDateTime(strtotime('first day of last month'))->setTime(0,0,0));
                $endDate = (clone $startDate)->add(new DateInterval('P1M'));
                break;
                
            case 'This Year':
                $startDate = DateTimeHelper::toDateTime(strtotime('first day of January'))->setTime(0,0,0);
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
                $startDate = DateTimeHelper::toDateTime(strtotime('first day of April'))->setTime(0,0,0);
                $endDate = (clone $startDate)->add(new DateInterval('P1Y'));
                break;
        }
        
        return ['start' => $startDate, 'end' => $endDate];
    }
}