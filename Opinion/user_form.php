<!-- The Modal -->
<div class="modal" id="myModal">
  <div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
      <div class="card container" id="card-for-form"
          style="width: 18rem; background:aliceblue; display:block; visibility:visible;">
          <div class="card-body">
            <div class="container">
              <div>
                <h2>Opinion 😊</h2>
                <hr>
              </div>
              <form class="row g-3"  method="POST">
                <div class="col-md-6">
                  <label for="name" class="form-label">Name</label>
                  <input type="text" class="form-control" name="name">
                </div>

                <div class="col-md-6">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" class="form-control" name="email">
                </div>

                <div class="col-md-6">
                  <label for="city" class="form-label">City</label>
                  <input type="text" name="city" class="form-control">
                </div>

                <div class="col-md-6">
                  <label for="rating" class="form-label">Rating</label>

                  <div class="rating"> 
                    <input type="radio" name="rating" value="5" id="5">
                    <label for="5">☆</label>
                    <input type="radio" name="rating" value="4" id="4">
                    <label for="4">☆</label>
                    <input type="radio" name="rating" value="3" id="3">
                    <label for="3">☆</label>
                    <input type="radio" name="rating" value="2" id="2">
                    <label for="2">☆</label>
                    <input type="radio" name="rating" value="1" id="1">
                    <label for="1">☆</label>
                  </div>

                </div>


                <div class="col-12">
                  <label for="comments" class="form-label">Comments about our site</label>
                  <textarea type="text" class="form-control" name="comments" placeholder="Your site is amazing">
                  </textarea>
                </div>

                <div class="col-12">
                  <button type="submit" name="btn_submit" class="btn btn-primary">Submit</button>
                </div>
              </form>

            </div>

            
          </div>
        </div>
        <!-- Card and Form end here  -->
      </div>

      
    </div>
  </div>
</div>

<style>
  .rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: left;
  }

  .rating>input {
    display: none
  }

  .rating>label {
    position: relative;
    width: 1em;
    font-size: 30px;
    font-weight: 300;
    color: #FFD600;
    cursor: pointer
  }

  .rating>label::before {
    content: "\2605";
    position: absolute;
    opacity: 0
  }

  .rating>label:hover:before,
  .rating>label:hover~label:before {
    opacity: 1 !important
  }

  .rating>input:checked~label:before {
    opacity: 1
  }

  .rating:hover>input:checked~label:before {
    opacity: 0.4
  }

  #card-for-form {
    /* margin-top: 20px; */
    /* visibility: hidden; */
    /* z-index: 99999999999; */
  }

  .btn1 {
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

  .modal{
    /* width: auto; */
    /* height: auto; */
    /* top: 50%; */
    /* left: 50%; */
    /* transform: translate(-50%, -50%); */
    /* position: fixed; */
    z-index: 99999999999;
    /* background-color: #ffffff; */
    /* border-radius: 10px; */
    /* padding: 20px; */
    /* box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.75); */
    /* border: none; */
    /* transition: all 0.5s ease-in-out; */
  }
</style>

<div  draggable="true" onclick="" class="btn1" id="btn1" data-bs-toggle="modal" data-bs-target="#myModal">😊</div>

<script>
  jQuery(document).ready(function ($) {
    $('#btn1').draggable();
    jQuery('$btn1').preventDefault();

   
  // jQuery('#btn1').click(function () {
    // jQuery('#myModal').modal('toggle');
    // });

  
  });
</script>