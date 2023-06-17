jQuery(document).ready(function($) {
  function fetch()
  {
    $.ajax({
        url: 'https://randomuser.me/api/',
        dataType: 'json',
        success:async function(data) {
          var gender = await data.results[0].gender;
          var title = await data.results[0].name.title;
          var first = await data.results[0].name.first;
          var last = await data.results[0].name.last;
          var cell = await data.results[0].cell;
          var dob = await data.results[0].dob.date;
          var age = await data.results[0].dob.age;
          var email = await data.results[0].email;
          var streetNo = await data.results[0].location.street.number;
          var streetName = await data.results[0].location.street.name;
          var city = await data.results[0].location.city;
          var state = await data.results[0].location.state;
          var phone = await data.results[0].phone;
          var picture = await data.results[0].picture.large;
          
          $('#gender').html('<strong>Gender: </strong>' + gender);
          $('#title').html('<strong>Title: </strong>' + title);
          $('#first').html('<strong>First Name: </strong>' + first);
          $('#last').html('<strong>Last Name: </strong>' + last);
          $('#cell').html('<strong>Cell: </strong>' + cell);
          $('#dob').html('<strong>DOB: </strong>' + dob);
          $('#age').html('<strong>Age: </strong>' + age);
          $('#email').html('<strong>Email: </strong>' + email);
          $('#streetNo').html('<strong>Street No: </strong>' + streetNo);
          $('#streetName').html('<strong>Street Name: </strong>' + streetName);
          $('#city').html('<strong>City: </strong>' + city);
          $('#state').html('<strong>State: </strong>' + state);
          $('#phone').html('<strong>Phone: </strong>' + phone);
          $('#picture').attr('src', picture);

          
        },
        error: function (request, status, error) {
          alert(request.responseText);
      }
      });
    }
    fetch();
    // $('#btn-modal').click();

    $('#again').click(function(){
      fetch();

    $('#btn-modal').draggable();
    });

});