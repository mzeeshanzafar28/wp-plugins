jQuery(document).ready(function ($) {

  window.addEventListener("beforeunload", setCookie);
  getCookie();

  function setCookie() {
    var clownDiv = document.getElementsByName("clown")[0];
    var elementsHTML = clownDiv.innerHTML;
    document.cookie = "countElements" + "=" + encodeURIComponent(elementsHTML) + "; path=/";
    setValues();
  }
  
  function getCookie() {
    var cookieName = "countElements=";
    var cookies = document.cookie.split(';');
    
    for (var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].trim();
      if (cookie.indexOf(cookieName) === 0) {
        var elementsHTML = decodeURIComponent(cookie.substring(cookieName.length));
        console.log(elementsHTML);
        var clownDiv = document.getElementsByName("clown")[0];
        clownDiv.innerHTML = elementsHTML;
        break;
      }
    }
    getValues();
  }

  function setValues()
  {
    var countDiv = document.getElementsByName("count")[0];
    var count = countDiv.id;
    var arr = [];
    for (var i=1; i<=count; i++)
    {
        var customFee = document.getElementById("custom-fee-input-" + i).value;
        arr.push(customFee);
    }

    var customFee = document.getElementById("custom-fee-input").value;
    arr.push(customFee);
    
    localStorage.setItem("customFee", JSON.stringify(arr));
  }

  function getValues()
  {
    arr = JSON.parse(localStorage.getItem("customFee"));
    var countDiv = document.getElementsByName("count")[0];
    var count = countDiv.id;
    for (var i=1; i<=count; i++)
    {
        document.getElementById("custom-fee-input-" + i).value = arr[i-1];
    
  }
  document.getElementById("custom-fee-input").value = arr[count];

  }


    $.ajax({
        type: 'POST',
        url: ajax_object.ajax_url,
        data: {
            action: 'update_custom_fee',
            custom_fee: 0,
        },
        success: function (response) {
            $('body').trigger('update_checkout');
        }
    });
    
    var send = 0;

    function add_field()
{
        var elements = document.getElementsByName("count");
        var count = elements[0].id;
        count++;
        
        document.getElementById("custom-fee-input-p").setAttribute("id","custom-fee-input-p-" + count);
        document.getElementById("custom-fee-input").setAttribute("id", "custom-fee-input-" + count);
        document.getElementById('custom-fee-input-'+count).setAttribute('name', 'custom-fee-input-' + count);
        document.getElementById("lab").setAttribute("id", "lab-" + count); 
        document.getElementById("lab-"+count).innerText = "Custom Fee-" + count;

        var element = document.getElementsByName("count")[0];
        element.setAttribute("id", count);
                 
                 var customField = `<p class="form-row form-row-wide" id="custom-fee-input-p" data-priority="110">
                 <label id="lab" for="custom-fee-input" class="">Custom Fee&nbsp;<abbr class="required" title="required">*</abbr></label>
                 <span class="woocommerce-input-wrapper">
                 <input type="text" class="input-text " maxlength="10" name="custom-fee-input" id="custom-fee-input">
                 </span>
                 </p>`;
                var elements = document.getElementsByName("count");
                var count = elements[0].id;
                 jQuery('#' + count).append(customField);
}


$('[name="count"]').on('input', '[id^="custom-fee-input-"]', function() {
    var len = $(this).val().length;
    if (len <= 5) {
      var currentIndex = parseInt($(this).attr('id').split('-')[3]);
      $('[id^="custom-fee-input-"]').each(function() {
        var index = parseInt($(this).attr('id').split('-')[3]);
        if (index > currentIndex) {
            var elements = document.getElementsByName("count");
            var count = elements[0].id;
            count--;
            var element = document.getElementsByName("count")[0];
            element.setAttribute("id", count);
          $(this).remove();
          $('#custom-fee-input-p-' + index).remove();
          $('#label-' + index).remove();
          //mine
          $('#custom-fee-input-p').remove();
          $('#custom-fee-input').remove();
          $('#lab').remove();
         
        }
var customFeeInputP = document.getElementById("custom-fee-input-p-" + count);
var customFeeInput = document.getElementById("custom-fee-input-" + count);
var customFeeInputField = document.getElementById('custom-fee-input-' + count);
var lab = document.getElementById("lab-" + count);
var labText = document.getElementById("lab-" + count);

if (customFeeInputP) {
  customFeeInputP.setAttribute("id", "custom-fee-input-p");
}

if (customFeeInput) {
  customFeeInput.setAttribute("id", "custom-fee-input");
}

if (customFeeInputField) {
  customFeeInputField.setAttribute('name', 'custom-fee-input');
}

if (lab) {
  lab.setAttribute("id", "lab");
}

if (labText) {
labText.innerText = "Custom Fee";
var elements = document.getElementsByName("count");
var count = elements[0].id;
count--;
var element = document.getElementsByName("count")[0];
element.setAttribute("id", count);
send = count;
}


      });
      
    }
  });
  

  $(document).on('input', '#custom-fee-input', function () {
  var flag = 0;
    var inputValue = $(this).val().trim();
    if (inputValue.length > 5) {
        if (flag == 0)
        {
            add_field();
            var elements = document.getElementsByName("count");
            var count = elements[0].id;
            send = count;
            flag = 1;
        }
    }
    // else{
    //     send = 0;
    // }
         $.ajax({
                type: 'POST',
                url: ajax_object.ajax_url,
                data: {
                    action: 'update_custom_fee',
                    custom_fee: send,
                },
                success: function (response) {
                    $('body').trigger('update_checkout');
                
                }
            });
     });

});
