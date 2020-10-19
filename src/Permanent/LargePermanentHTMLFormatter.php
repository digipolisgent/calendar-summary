<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 06/03/15
 * Time: 14:38
 */

namespace CultuurNet\CalendarSummary\Permanent;

use \CultureFeed_Cdb_Data_Calendar_SchemeDay as SchemeDay;

class LargePermanentHTMLFormatter implements PermanentFormatterInterface
{
    /**
     * Translate the day in Dutch.
     */
    protected $mapping_days = array(
        'monday' => 'maandag',
        'tuesday' => 'dinsdag',
        'wednesday' => 'woensdag',
        'thursday' => 'donderdag',
        'friday' => 'vrijdag',
        'saturday' => 'zaterdag',
        'sunday' => 'zondag',
    );

    protected $mapping_short_days = array(
        'monday' => 'Mo',
        'tuesday' => 'Tu',
        'wednesday' => 'We',
        'thursday' => 'Th',
        'friday' => 'Fr',
        'saturday' => 'Sa',
        'sunday' => 'Su',
    );

    public function format(
        \CultureFeed_Cdb_Data_Calendar_Permanent $permanent
    ) {
        $output = '';
        if (!is_null($permanent->getWeekScheme())) {
            $output .= $this->generateWeekscheme($permanent->getWeekScheme());
        }

        return $this->formatSummary($output);
    }

    protected function generateWeekscheme($weekscheme)
    {
        $output_week = '<ul class="list-unstyled">';

        $keys = array_keys($weekscheme->getDays());

        for ($i = 0; $i <= 6; $i++) {
            $one_day = $weekscheme->getDays()[$keys[$i]];
            if (!is_null($one_day) && $one_day->getOpenType()==SchemeDay::SCHEMEDAY_OPEN_TYPE_OPEN) {
                $previous=null;
                if ($one_day->getDayName()!=SchemeDay::MONDAY) {
                    $previous = $weekscheme->getDays()[$keys[$i - 1]];
                }
                if (!is_null($previous) &&
                    $previous->getOpenType()==$one_day->getOpenType() &&
                    $previous->getOpeningTimes()==$one_day->getOpeningTimes()) {
                    $one_day_dutch = $this->getDutchDay($one_day->getDayName());
                    $previous_dutch = $this->getDutchDay($previous->getDayName());
                    $one_day_short= $this->mapping_short_days[$one_day->getDayName()];
                    $previous_short=$this->mapping_short_days[$previous->getDayName()];

                    if (strpos($output_week, '- ' . $previous_dutch . '</span>') != false) {
                        $output_week = str_replace(
                            '- ' . $previous_dutch . '</span>',
                            '- ' . $one_day_dutch . '</span>',
                            $output_week
                        );
                        $output_week = str_replace(
                            '-' . $previous_short . ' ',
                            '-' . $one_day_short . '</span>',
                            $output_week
                        );
                    } else {
                        $output_week = str_replace(
                            ucfirst($previous_dutch) . '</span>',
                            ucfirst($previous_dutch) . ' - ' .$one_day_dutch . '</span>',
                            $output_week
                        );
                        $output_week = str_replace(
                            'datetime="' . $previous_short . ' ',
                            'datetime="' . $previous_short . '-' . $one_day_short . ' ',
                            $output_week
                        );
                        $output_week = str_replace(
                            '- ' . $previous_dutch . '</span>',
                            '- ' . $one_day_dutch . '</span>',
                            $output_week
                        );
                        $output_week = str_replace(
                            '-' . $previous_short . ' ',
                            '-' . $one_day_short . ' ',
                            $output_week
                        );
                    }
                } else {
                    $one_day_dutch = ucfirst($this->getDutchDay($one_day->getDayName()));
                    //$output_week .= '<li>';
                    $output_week .= '<meta itemprop="openingHours" datetime="'
                        . $this->mapping_short_days[$one_day->getDayName()]
                        . $this->getEarliestTime($one_day->getOpeningTimes())
                        . $this->getLatestTime($one_day->getOpeningTimes()) . '"></meta>';
                    $output_week .= '<li itemprop="openingHoursSpecification">';
                    $output_week .= '<span class="cf-days">' . $one_day_dutch . '</span>';
                    if (!is_null($one_day->getOpeningTimes())) {
                        foreach ($one_day->getOpeningTimes() as $opening_time) {
                            $output_week .= '<span itemprop="opens" content="'
                                . $this->getFormattedTime($opening_time->getOpenFrom())
                                . '" class="cf-from cf-meta">van</span>';
                            $output_week .= $this->getFormattedTime($opening_time->getOpenFrom());
                            if (!is_null($opening_time->getOpenTill())) {
                                $output_week .= '<span itemprop="closes" content="';
                                $output_week .= $this->getFormattedTime($opening_time->getOpenTill());
                                $output_week .= '" class="cf-to cf-meta">tot</span>';
                                $output_week .= $this->getFormattedTime($opening_time->getOpenTill());
                            }
                        }
                    }
                    //$output_week .= '</meta>';
                    $output_week .= '</li>';
                    //$output_week .= '</li>';
                }
            }
        }
        $output_week .= '</ul>';

        return $output_week;
    }

    protected function getDutchDay($day)
    {
        return $this->mapping_days[$day];
    }

    protected function getFormattedTime($time)
    {
        $formatted_time = substr($time, 0, -3);
        $formatted_short_time = ltrim($formatted_time, '0');
        if ($formatted_short_time == ':00') {
            $formatted_short_time = '0:00';
        }
        return $formatted_short_time;
    }

    protected function formatSummary($calsum)
    {
        $calsum = str_replace('><', '> <', $calsum);
        return str_replace('  ', ' ', $calsum);
    }

    protected function getEarliestTime($times)
    {
        $start_time = null;
        foreach ($times as $time) {
            if ($start_time==null || $start_time > $time->getOpenFrom()) {
                $start_time = $time->getOpenFrom();
            }
        }
        if (is_null($start_time)) {
            return '';
        } else {
            return ' ' . $this->getFormattedTime($start_time);
        }
    }

    protected function getLatestTime($times)
    {
        $end_time = null;
        foreach ($times as $time) {
            if ($end_time==null || $end_time < $time->getOpenTill()) {
                $end_time = $time->getOpenTill();
            }
        }
        if (is_null($end_time)) {
            return '';
        } else {
            return '-' . $this->getFormattedTime($end_time);
        }
    }
}
