<?php

namespace Slub\SlubFindExtend\Services;

/**
 * Class StopWordService
 */
class StopWordService
{
    /**
     * @return list<string>
     */
    private function getStopWords(): array
    {
        return ['A', 'ALS', 'AM', 'AN', 'AND', 'ARE', 'AS', 'AT', 'AUF', 'AUS', 'BE', 'BUT', 'BY', 'DAS', 'DASS', 'DAß', 'DER', 'DICH', 'DIE', 'DIR', 'DU', 'DURCH', 'EINE', 'EINEM', 'EINEN', 'EINER', 'EINES', 'ER', 'ES', 'FOR', 'FÜR', 'IF', 'IHR', 'IHRE', 'IHRES', 'IM', 'IN', 'INTO', 'IS', 'IST', 'IT', 'KEIN', 'MEIN', 'MICH', 'MIR', 'MIT', 'NO', 'NOT', 'ODER', 'OF', 'OHNE', 'ON', 'OR', 'S', 'SEIN', 'SIE', 'SUCH', 'T', 'THAT', 'THE', 'THEIR', 'THEN', 'THERE', 'THESE', 'THEY', 'THIS', 'TO', 'UND', 'VON', 'WAR', 'WAS', 'WEGEN', 'WER', 'WIE', 'WILL', 'WIR', 'WIRD', 'WITH'];
    }

    /**
     * @param string $querystring
     * @return string
     */
    private function stripPuntuations(string $querystring): string
    {
        return str_replace([',', '.', ':', ';', '?', '!', '\'', '(', ')', '&', '$', '[', ']'], [], $querystring);
    }

    /**
     * @param string $querystring
     * @return string
     */
    private function stripStopWords(string $querystring): string
    {
        $querystringPieces = explode(' ', $querystring);

        $querystringPieces = array_diff($querystringPieces, $this->getStopWords());

        return implode(' ', $querystringPieces);
    }

    /**
     * @param string $querystring
     * @return string
     */
    public function cleanQueryString(string $querystring): string
    {
        if (preg_match('/^".*"$/', trim($querystring)) === 1) {
            return $querystring;
        }

        return $this->stripStopWords($this->stripPuntuations(mb_strtoupper($querystring, 'UTF-8')));
    }
}
