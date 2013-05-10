<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_charts extends WYSIJA_object {
    function WYSIJA_help_charts() {
    }
    function pieChart($id, $options = array()) {
        return $this->drawChart('piechart', $id, $options);
    }
    function columnStacked($id, $options = array()) {
        return $this->drawChart('column', $id, $options);
    }
    function drawChart($type, $id, $options = array()) {
        $id = str_replace(' ', '-', $id);
        $width = (isset($options['width'])) ? (int)$options['width'] : 400;
        $height = (isset($options['height'])) ? (int)$options['height'] : 225;
        $title = (isset($options['title'])) ? $options['title'] : null;
        $url = (isset($options['url'])) ? $options['url'] : null;
        $data = (isset($options['data'])) ? $options['data'] : null;
        $titleField = (isset($options['titleField'])) ? $options['titleField'] : null;
        $valueField = (isset($options['valueField'])) ? $options['valueField'] : null;
        $categoryField = (isset($options['categoryField'])) ? $options['categoryField'] : null;
        $is_3d = (isset($options['3D']) && (bool)$options['3D'] === true);
        $graphs = (isset($options['graphs'])) ? $options['graphs'] : null;
        $content = '<div id="wysija-chart-'.$id.'" class="wysija-chart '.$type.'" style="width:'.$width.'px;height:'.$height.'px;"></div>';
        $content .= '<script type="text/javascript">';
        $content .= 'AmCharts.ready(function () {';
        $content .= '   WysijaCharts.createChart("wysija-chart-'.$id.'", {';
        if($is_3d === true)         $content .= 'threeD: true,';
        if($url !== null)           $content .= 'url: "'.$url.'",';
        if($data !== null)          $content .= 'data: '.json_encode($data).',';
        if($title !== null)         $content .= 'title: "'.$title.'",';
        if($titleField !== null)    $content .= 'titleField: "'.$titleField.'",';
        if($valueField !== null)     $content .= 'valueField: "'.$valueField.'",';
        if($categoryField !== null)     $content .= 'categoryField: "'.$categoryField.'",';
        if($graphs !== null)        $content .= 'graphs: '.json_encode($graphs).',';
        $content .= '       type: "'.$type.'"';
        $content .= '   });';
        $content .= '});';
        $content .= '</script>';
        return $content;
    }
}