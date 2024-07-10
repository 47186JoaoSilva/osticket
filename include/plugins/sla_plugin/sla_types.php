<?php

enum Holidays: string{
    case NewYears = '01/01';
    case MartinLutherKingDay = '0'; // Terceira segunda-feira de Janeiro
    case PresidentDay = '1'; // Terceira segunda-feira de Fevereiro
    case MemorialDay = '2'; // Última segunda-feira de Maio
    case Juneteenth = '19/06'; 
    case IndependenceDay = '04/07';
    case LaborDay = '3'; // Primeira segunda-feira de Setembro
    case WarVeteranDay = '11/11';
    case Thanksgiving = '4'; // Quarta quinta-feira de Novembro
    case Christmas = '25/12';
    
    public static function getHolidaysValues(): array {
        $a = array_map(fn($case) => $case->value, self::cases());
        $ar = array();
        foreach ($a as $key => $value) {
            $ar[] = $value;
        }
        return $ar;
    }
}

function getNthWeekdayOfMonth($year, $month, $weekday, $nth) {
    $firstDayOfMonth = strtotime("$year-$month-01");
    $firstWeekday = date('N', $firstDayOfMonth);
    $dayOffset = ($weekday - $firstWeekday + 7) % 7;
    $nthWeekdayTimestamp = strtotime("+$dayOffset days", $firstDayOfMonth);
    $nthWeekdayTimestamp = strtotime("+".($nth - 1)." weeks", $nthWeekdayTimestamp);
    return date('d/m', $nthWeekdayTimestamp);
}

function getLastWeekdayOfMonth($year, $month, $weekday) {
    $lastDayOfMonth = strtotime("last day of $year-$month");
    $lastWeekday = date('N', $lastDayOfMonth);
    $dayOffset = ($lastWeekday - $weekday + 7) % 7;
    $lastWeekdayTimestamp = strtotime("-$dayOffset days", $lastDayOfMonth);
    return date('d/m', $lastWeekdayTimestamp);
}

function validateHolidayDates() {
    $year = date("Y");
    return [
        Holidays::MartinLutherKingDay->name => getNthWeekdayOfMonth($year, 1, 1, 3),
        Holidays::PresidentDay->name => getNthWeekdayOfMonth($year, 2, 1, 3),
        Holidays::MemorialDay->name => getLastWeekdayOfMonth($year, 5, 1),
        Holidays::LaborDay->name => getNthWeekdayOfMonth($year, 9, 1, 1),
        Holidays::Thanksgiving->name => getNthWeekdayOfMonth($year, 11, 4, 4)
    ];
}

class sla_types {
    private $list_sla_types;

    public function __construct() {
        $this->list_sla_types = array(
            '24/7' => array('monday','tuesday','wednesday', 'thursday', 'friday', 'saturday','sunday'), // 24 horas todos os dias
            '24/5' => array('monday','tuesday','wednesday', 'thursday', 'friday'), // 24 horas, apenas dias úteis
            'U.S. Holidays' => Holidays::getHolidaysValues(), // 24 horas por feriado
            'Monday - Friday 8am - 5pm with U.S. Holidays' => array('monday','tuesday','wednesday', 'thursday', 'friday') // 9 horas diárias, apenas dias úteis
        );
    }
    
    function check_if_schedule($ticket_id){
        $get_sla_query = "SELECT ose.name FROM ost_ticket ott
                        INNER JOIN ost_sla osa ON osa.id = ott.sla_id
                        INNER JOIN ost_schedule ose ON ose.id = osa.schedule_id
                        WHERE ott.ticket_id = $ticket_id;";
        $result = db_query($get_sla_query);

        $schedule_name = '';
        while ($row = db_fetch_array($result)) {
            $schedule_name = $row['name'];
        }
        return $this->list_sla_types[$schedule_name] ?? [];
    }
    
    function check_if_date_is_holiday($date){
        $date_formatted = DateTime::createFromFormat('d-m-Y', $date); 
        if(!($date_formatted && $date_formatted->format('d-m-Y') === $date)){
            return false;
        }
        $day = date('d', strtotime($date)); 
        $month = date('m', strtotime($date));
        $formattedDate = "$day/$month";
        $holidayDates = validateHolidayDates();
        if (in_array($formattedDate, $holidayDates)) {
            return true;
        }
        foreach (Holidays::cases() as $holiday) {
            if ($holiday->value === $formattedDate) {
                return true;
            }
        }
        return false;
    }
    
    public function getSusHours($beginDate, $endDate, $slaName, $ticket_id){
        $begin = DateTime::createFromFormat('Y-m-d H:i:s', $beginDate);
        $end = DateTime::createFromFormat('Y-m-d H:i:s', $endDate);

        if (!$begin || !$end || $begin > $end) {
            throw new InvalidArgumentException("Invalid dates provided.");
        }        
        
        $begin_time_hour = (int)$begin->format('H');
        $end_time_hour = (int)$end->format('H');
        $begin_time_minute = (int)$begin->format('i');
        $end_time_minute = (int)$end->format('i');
        $time_diff = ($end_time_hour - $begin_time_hour) + ($end_time_minute - $begin_time_minute) / 60;

        $get_suspension_time_query = "SELECT suspension_time FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '$ticket_id' AND act_flag = 1 AND end_suspension IS NOT NULL ORDER BY tid DESC;";
        $result = db_query($get_suspension_time_query);

        $suspension_time = 0;
        if ($result) {
            while ($row = db_fetch_array($result)) {
                $suspension_time = (int)$row['suspension_time'];
                break;
            }
        }
        
        $hours = $suspension_time; 
        
        $slaDays = $this->list_sla_types[$slaName] ?? [];
        $new_end = clone $end;
        $new_end = $new_end->modify('+1 day');
        $new_begin = clone $begin;
        $new_begin = $new_begin->modify('+1 day');
        for ($date = clone $new_begin; $date <= $new_end; $date->modify('+1 day')) {
            $dayName = strtolower($date->format('l'));
            if ($slaName === '24/7') {
                $hours = $this->sla_24_7($hours, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff);
            } elseif ($slaName === '24/5') {
                $hours = $this->sla_24_5($hours, $dayName, $slaDays, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff);
            } elseif ($slaName === 'U.S. Holidays') {
                $hours = $this->sla_holidays($hours, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff);
            } elseif ($slaName === 'Monday - Friday 8am - 5pm with U.S. Holidays') {
                $hours = $this->sla_8_17($hours, $dayName, $slaDays, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff);
            }
        }

        return $hours;
    }
    
    // Private Methods
    
    private function sla_24_7($hours_sum, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff){
        $hours = $hours_sum;
        if ($this->check_if_date_is_holiday($date->format('d-m-Y'))) {
            if($begin->format('Y-m-d') === $end->format('Y-m-d')){
                return $hours;
            }
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if ($this->check_if_date_is_holiday($begin->format('d-m-Y'))) {
                    return $hours;
                }
                return $hours + (24-($begin_time_hour + ($begin_time_minute/60)));
            }
            return $hours;
        }
        if($begin->format('Y-m-d') === $end->format('Y-m-d')){
            return $hours + $time_diff;
        }
        if($date->format('Y-m-d') !== $begin->format('Y-m-d') && $date->format('Y-m-d') !== $end->format('Y-m-d')){
            return $hours + 24;
        }
        if($date->format('Y-m-d') === $end->format('Y-m-d')){
            return $hours + $end_time_hour + ($end_time_minute/60) + (24-($begin_time_hour + ($begin_time_minute/60)));
        }
        return $hours;
    }
    
    private function sla_24_5($hours_sum, $dayName, $slaDays, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff){
        $hours = $hours_sum;
        if ($this->check_if_date_is_holiday($date->format('d-m-Y'))) {
            if($begin->format('Y-m-d') === $end->format('Y-m-d')){
                return $hours;
            }
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if ($this->check_if_date_is_holiday($begin->format('d-m-Y')) || !in_array(strtolower($begin->format('l')), $slaDays)) {
                    return $hours;
                }
                return $hours + (24-($begin_time_hour + ($begin_time_minute/60)));
            }
            return $hours;
        }
        if (in_array($dayName, $slaDays)) {
            if($begin->format('Y-m-d') === $end->format('Y-m-d')){
                return $hours + $time_diff;
            }
            if($date->format('Y-m-d') !== $begin->format('Y-m-d') && $date->format('Y-m-d') !== $end->format('Y-m-d')){
                return $hours + 24;
            }
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                return $hours + $end_time_hour + ($end_time_minute/60) + (24-($begin_time_hour + ($begin_time_minute/60)));
            }  
        }
        else{
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if ($this->check_if_date_is_holiday($begin->format('d-m-Y')) || !in_array(strtolower($begin->format('l')), $slaDays)) {
                    return $hours;
                }
                return $hours + (24-($begin_time_hour + ($begin_time_minute/60)));
            }
        }
        return $hours;
    }
    
    private function sla_holidays($hours_sum, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff){
        $hours = $hours_sum;
        if ($this->check_if_date_is_holiday($date->format('d-m-Y'))) {
            if($begin->format('Y-m-d') === $end->format('Y-m-d')){
                return $hours + $time_diff;
            }
            if($date->format('Y-m-d') !== $begin->format('Y-m-d') && $date->format('Y-m-d') !== $end->format('Y-m-d')){
               return $hours + 24;
            }
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if (!$this->check_if_date_is_holiday($begin->format('d-m-Y'))) {
                    return $hours + $end_time_hour + ($end_time_minute/60);
                }
                return $hours + $end_time_hour + ($end_time_minute/60) + (24-($begin_time_hour + ($begin_time_minute/60)));
            }
        }
        else{
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if (!$this->check_if_date_is_holiday($begin->format('d-m-Y'))) {
                    return $hours;
                }
                return $hours + (24-($begin_time_hour + ($begin_time_minute/60)));
            }
        }
        return $hours;
    }
    
    private function sla_8_17($hours_sum, $dayName, $slaDays, $begin, $end, $date, $begin_time_hour, $begin_time_minute, $end_time_hour, $end_time_minute, $time_diff){
        $hours = $hours_sum;
        if (in_array($dayName, $slaDays)) {
            if($begin->format('Y-m-d') === $end->format('Y-m-d')){
                return $hours + $time_diff;
            }
            if($date->format('Y-m-d') !== $begin->format('Y-m-d') && $date->format('Y-m-d') !== $end->format('Y-m-d')){
               return $hours + 9;
            }
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if (in_array(strtolower($begin->format('l')), $slaDays)) {
                    if($begin_time_hour < 8){
                        $hours += 9;
                    }
                    else{
                        $hours += (17 - ($begin_time_hour + ($begin_time_minute/60)));
                    }
                }                        
                if($end_time_hour > 17){
                    $hours += 9;
                }
                else{
                    $hours += (($end_time_hour + ($end_time_minute/60)) - 8);
                }
                return $hours;
            }
        }
        else{
            if($date->format('Y-m-d') === $end->format('Y-m-d')){
                if (in_array(strtolower($begin->format('l')), $slaDays)) {
                    if($begin_time_hour < 8){
                        $hours += 9;
                    }
                    else{
                        $hours += (17 - ($begin_time_hour + ($begin_time_minute/60)));
                    }
                }                        
                return $hours;
            }
        }
        return $hours;
    }
}