<table>
  <tr>
    <th class="jour"><?php _e('sun', 'if-calendar')?></th>
    <th class="jour">mon</th>
    <th class="jour">tue</th>
    <th class="jour">wed</th>
    <th class="jour">thu</th>
    <th class="jour">fri</th>
    <th class="jour">sat</th>
  </tr>
  __cal_rows__
</table>
<div class="navigation">
    <a class="prev" href="index.php?month=__prev_month__" onclick="$('#calendar').load('index.php?month=__prev_month__&_r=' + Math.random()); return false;"></a>
    <div class="mois" >__cal_caption__</div>
    <a class="next" href="index.php?month=__next_month__" onclick="$('#calendar').load('index.php?month=__next_month__&_r=' + Math.random()); return false;"></a>
</div>
