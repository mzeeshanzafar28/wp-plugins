<?php
function my_btn()
{
  ?>
  <style>
    * {
      font-family: sans-serif;
    }

    .btn {
      height: 80px;
      line-height: 80px;
      width: 80px;
      font-size: 2em;
      font-weight: bold;
      border-radius: 50%;
      background-color: blueviolet;
      color: white;
      text-align: center;
      cursor: pointer;
      z-index: 99999999999;
      position: fixed;
    }
  </style>

  <script src="https://code.jquery.com/jquery-3.6.1.js" integrity="sha256-3zlB5s2uwoUzrXK3BT7AX3FyvojsraNFxCc2vC/7pNI="
    crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
  <script>

    function switchState() {
      if (flag == 0) {
        flag++;
        initial();
      }

      defState = !defState;
      revert();
    }
    jQuery(document).ready(function () {
      $("#btn").draggable();
      $("#btn").click(switchState);
    });





    var defState = true;
    var defs = [];
    var flag = 0;
    // background, h1, h2, h3, h4, h5, h6, p, strong , em , a, hr, b, i, small, sub, sup, ins, del, mark
    var properties = ['black', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white', 'white'];

    function initial() {
      var bkg = jQuery('body').css('background');
      var h1 = jQuery('h1').css('color');
      var h2 = jQuery('h2').css('color');
      var h3 = jQuery('h3').css('color');
      var h4 = jQuery('h4').css('color');
      var h5 = jQuery('h5').css('color');
      var h6 = jQuery('h6').css('color');
      var p = jQuery('p').css('color');
      var strong = jQuery('strong').css('color');
      var em = jQuery('em').css('color');
      var a = jQuery('a').css('color');
      var hr = jQuery('hr').css('color');
      var b = jQuery('b').css('color');
      var i = jQuery('i').css('color');
      var small = jQuery('small').css('color');
      var sub = jQuery('sub').css('color');
      var sup = jQuery('sup').css('color');
      var ins = jQuery('ins').css('color');
      var del = jQuery('del').css('color');
      var mark = jQuery('mark').css('color');


      defs = [bkg, h1, h2, h3, h4, h5, h6, p, strong, em, a, hr, b, i, small, sub, sup, ins, del, mark];
    }

    

    function revert() {
      if (defState) {
        jQuery('body').css('background', defs[0]);
        jQuery('h1').css('color', defs[1]);
        jQuery('h2').css('color', defs[2]);
        jQuery('h3').css('color', defs[3]);
        jQuery('h4').css('color', defs[4]);
        jQuery('h5').css('color', defs[5]);
        jQuery('h6').css('color', defs[6]);
        jQuery('p').css('color', defs[7]);
        jQuery('strong').css('color', defs[8]);
        jQuery('em').css('color', defs[9]);
        jQuery('a').css('color', 'inherit');
        jQuery('hr').css('color', defs[11]);
        jQuery('b').css('color', defs[12]);
        jQuery('i').css('color', defs[13]);
        jQuery('small').css('color', defs[14]);
        jQuery('sub').css('color', defs[15]);
        jQuery('sup').css('color', defs[16]);
        jQuery('ins').css('color', defs[17]);
        jQuery('del').css('color', defs[18]);
        jQuery('mark').css('color', defs[19]);




      }
      else {
        jQuery('body').css('background', properties[0]);
        jQuery('h1').css('color', properties[1]);
        jQuery('h2').css('color', properties[2]);
        jQuery('h3').css('color', properties[3]);
        jQuery('h4').css('color', properties[4]);
        jQuery('h5').css('color', properties[5]);
        jQuery('h6').css('color', properties[6]);
        jQuery('p').css('color', properties[7]);
        jQuery('strong').css('color', properties[8]);
        jQuery('em').css('color', properties[9]);
        jQuery('a').css('color', properties[10]);
        jQuery('hr').css('color', properties[11]);
        jQuery('b').css('color', properties[12]);
        jQuery('i').css('color', properties[13]);
        jQuery('small').css('color', properties[14]);
        jQuery('sub').css('color', properties[15]);
        jQuery('sup').css('color', properties[16]);
        jQuery('ins').css('color', properties[17]);
        jQuery('del').css('color', properties[18]);
        jQuery('mark').css('color', properties[19]);

      }

    }
  </script>

  <div class="btn" id="btn" draggable="true" >&#9816;</div>

  <?php
}
?>