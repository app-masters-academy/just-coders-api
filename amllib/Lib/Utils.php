<?php

namespace AppMasters\AmLLib\Lib;

class Utils
{
    public static function undoBrilhante($texto)
    {
        $letras = array("x", "b", "r", "i", "l", "h", "a", "n", "t", "e");
        $regex = sprintf('/[^%s]/u', preg_quote(join($letras), '/'));
        $texto = preg_replace($regex, '', $texto);
        if ($texto == null || $texto == "")
            return null;

        //$texto = str_replace($ruido,'',strtoupper($texto));
        $result = "";
        for ($i = 0; $i < strlen($texto); $i++) {
            $letra = $texto[$i];
            $index = array_search($letra, $letras);
            if ($index < 0 || $index > 10)
                continue;
            $result .= $index;
        }

        if ($result == "")
            return false;

        return $result;
    }

    public static function doBrilhante($inteiro, $muitoRuido = false)
    {
        $letras = "xbrilhante";
        $ruido = "cdfgjkopqu";
        $ruidoDenso = "-KJOWQY_PSZ";
        $brilhante = "";
        $inteiro = (string)$inteiro;

        for ($i = 0; $i < strlen($inteiro); $i++) {
            $numero = $inteiro[$i];
            $brilhante = $brilhante . $letras[$numero];
            if ($i % 3 == 0)
                $brilhante .= $ruido[$numero];
            if ($muitoRuido && $i % 2 == 0)
                $brilhante .= $ruidoDenso[$numero];
        }

        return $brilhante;
    }

    public static function validateCpf($cpf)
    {
        if ($cpf == null || $cpf == '')
            return false;
        $cpf = preg_replace('/[^0-9]/', '', (string)$cpf);
        // Valida tamanho
        if (strlen($cpf) != 11)
            return false;
        // Calcula e confere primeiro dígito verificador
        for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
            $soma += $cpf{$i} * $j;
        $resto = $soma % 11;
        if ($cpf{9} != ($resto < 2 ? 0 : 11 - $resto))
            return false;
        // Calcula e confere segundo dígito verificador
        for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
            $soma += $cpf{$i} * $j;
        $resto = $soma % 11;
        return $cpf{10} == ($resto < 2 ? 0 : 11 - $resto);
    }

    public static function validateCnpj($cnpj)
    {
        if ($cnpj == null || $cnpj == '')
            return false;
        $cnpj = preg_replace('/[^0-9]/', '', (string)$cnpj);
        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;
        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
            return false;
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj{13} == ($resto < 2 ? 0 : 11 - $resto);
    }

    // Move to LIB
    public static function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * DOC! What is does?
     * @param $array
     * @return array
     */
    public static function combine($array)
    {
        $nArray = [];
        foreach ($array as $k => $value) {
            if (!is_array($value))
                $nArray[$value] = $value;
            else
                $nArray[$k] = self::combine($value);
        }

        return $nArray;
    }


    public static function applyMask($array, $showMask = null, $hideMask = null)
    {
        $newArray = [];

        $showAll = isset($showMask['*']) || (isset($showMask[0]) && $showMask[0] == "*");

        if (isset($showMask))
            $showMask = Utils::combine($showMask);

        if (isset($hideMask)) {
            // var_dump($hideMask);
            $hideMask = Utils::combine($hideMask);
            // var_dump($hideMask);
            // die();
        }

        // A record, is assoc > 'field' => 'value
        if (Utils::isAssoc($array)) {
            // We are handling one record here
            $record = $array;

            $newRecord = $record;

            // Extract array attributes
            $arrayAttributes = array_keys($record);
            foreach ($arrayAttributes as $attribute) {

                //
                // var_dump(isset($hideMask));
                // var_dump($hideMask[$attribute]);
                // echo "<hr>";

                if (isset($showMask)) {
                    if (isset($showMask[$attribute])) {
                        if (is_array($record[$attribute])) {
                            $newRecord[$attribute] = self::applyMask($record[$attribute], $showMask[$attribute]);
                            $showMask[$attribute] = $attribute;
                        }
                    } else if (!$showAll) {
                        unset($newRecord[$attribute]);
                    }
                }
                if (isset($hideMask) && isset($hideMask[$attribute])) {
                    // var_dump("HEE");
                    if (is_array($record[$attribute])) {
                        // var_dump("ARRAY ".$attribute);
                        $newRecord[$attribute] = self::applyMask($record[$attribute], null, $hideMask[$attribute]);
                        $hideMask[$attribute] = $attribute;
                    } else {
                        // var_dump("UNSET ".$attribute);
                        unset($newRecord[$attribute]);
                    }
                }
            }
            $newArray = $newRecord;
        } else {
            foreach ($array as $k => $record) {
                $array[$k] = self::applyMask($record, $showMask, $hideMask);
            }
            $newArray = $array;
        }

        return $newArray;
    }


    /**
     * Aplly mask to string
     * Example:
     * $value=3288735683;
     * $mask=(##)####-####;
     * echo str_mask($value,$mask); // (32)8873-5683
     * @param $val
     * @param $mask
     * @return string
     */
    static function str_mask($val, $mask)
    {
        $masked = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k]))
                    $masked .= $val[$k++];
            } else {
                if (isset($mask[$i]))
                    $masked .= $mask[$i];
            }
        }
        return $masked;
    }

    /**
     * Return just the numbers from a string
     * @param $str
     * @return mixed
     */
    static function only_numbers($str)
    {
        return preg_replace('/\D/', "", $str);
    }

    public static function nameSurname(string $name)
    {
        return self::nameFirst($name) . ' ' . self::surname($name);
    }

    public static function surname(string $name)
    {
        $aName = explode(" ", mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, "UTF-8"));
        $segundo = null;
        if (count($aName) >= 2)
            $segundo = $aName[1];
        if (in_array(strtolower($segundo), array('de', 'da', 'do', 'dos', 'das', 'del', 'dal', 'das')) && count($aName) >= 3)
            $segundo = $aName[1] . ' ' . $aName[2];
        return $segundo;
    }

    public static function nameFirst(string $name)
    {
        $aName = explode(" ", mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, "UTF-8"));
        if (count($aName) == 1)
            return $name;
        else
            return $aName[0];
    }


}
