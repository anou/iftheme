<?php 
$data=json_decode(base64_decode($_REQUEST['data']),true);

?>
jQuery(function($){
    /*snippet for activation of the name field*/
    $('.needInfo').mouseover(function(){
        $(this).validationEngine('showPrompt', $(this).attr('alt'), 'pass');
    });
    $('.needInfo').mouseout(function(){
        $(this).validationEngine('hidePrompt');
    });
 
});
<?php if(isset($data['stats']) && empty($data['stats']) === FALSE) { ?>
// Load the Visualization API and the piechart package.
          google.load('visualization', '1.0', {'packages':['corechart']});
          // Set a callback to run when the Google Visualization API is loaded.
          google.setOnLoadCallback(drawChart);
          // Callback that creates and populates a data table, 
          // instantiates the pie chart, passes in the data and
          // draws it.
          function drawChart() {
              // Create the data table.
              var data = new google.visualization.DataTable();
              data.addColumn('string', 'Topping');
              data.addColumn('number', 'Slices');
              data.addRows([
                <?php 
                function htmlEncode($s) {
                    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
                }

                   // utf8_encode($data)
                    //utf8_decode($data)
                    foreach($data['stats'] as $stats){
                        echo "['".htmlEncode($stats['name'])."',   ".(int)$stats['number']."],";
                    }
                ?>
              ]);
              // Set chart options
              var options = {'title':'<?php 
              //if (!is_string($stats['title']) OR preg_match('|[^a-z0-9#_. -]|i',$stats['title']) !== 0 ) $stats['title']="Default";
              echo htmlEncode($data['title']);?>',
                             'width':400,
                             'height':200,
                             'backgroundColor':'transparent'};
              // Instantiate and draw our chart, passing in some options.
              var chart = new google.visualization.PieChart(document.getElementById('statscontainer'));
              chart.draw(data, options);
            }
<?php } ?>