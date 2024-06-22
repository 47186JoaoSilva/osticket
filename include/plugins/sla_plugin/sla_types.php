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
        
        $hours = $suspension_time + $time_diff;
        
        if($begin->format('Y-m-d') === $end->format('Y-m-d')){
            return $hours;
        }        
        
        $slaDays = $this->list_sla_types[$slaName] ?? [];
        for ($date = clone $begin; $date <= $end; $date->modify('+1 day')) {
            $dayName = strtolower($date->format('l'));
            if ($slaName === '24/7') {
                if ($this->check_if_date_is_holiday($date->format('d-m-Y'))) {
                    continue;
                }
                $hours += 24;
            } elseif ($slaName === '24/5') {
                if ($this->check_if_date_is_holiday($date->format('d-m-Y'))) {
                    continue;
                }
                if (in_array($dayName, $slaDays)) {
                    $hours += 24;
                }
            } elseif ($slaName === 'U.S. Holidays') {
                if ($this->check_if_date_is_holiday($date->format('d-m-Y'))) {
                    $hours += 24;
                }
            } elseif ($slaName === 'Monday - Friday 8am - 5pm with U.S. Holidays') {
                if (in_array($dayName, $slaDays)) {
                    $hours += 9;
                }
            }
        }

        return $hours;
    }
}