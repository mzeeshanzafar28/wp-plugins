<?php
/*
Plugin Name: Random User
Author: Muhammad Zeeshan Zafar
Description: Random User is a WP plugin that displays random users from an API.
Author URI: https://github.com/mzeeshanzafar28
Version: 1.0.0
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    die("Something went wrong");
}

// Add admin menu option
add_action('admin_menu', 'custom_function1');
function custom_function1()
{
  add_menu_page('Random User', 'Random User', 'manage_options', 'random-user', 'custom_function2', plugins_url('assets/images/random-user.png', __FILE__));
}

// Render options page
function custom_function2()
{
  echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- css  -->
    <style>
       body{
        background:black;
       }
    /* 
       img{
        height: 1100px;
    
       } */
    
    </style>
    <!-- carousel  -->
    
    <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
      </div>
      <div class="carousel-inner d-flex" >
        <div class="carousel-item active">
          <img src="https://images.unsplash.com/photo-1598723106396-f89827f6aa1a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1173&q=80" class="d-block w-100" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h5>Remember Allah is with you</h5>
            <p>AxionTech will always work devoting their enthusiasm to Allah.</p>
          </div>
        </div>
        <div class="carousel-item">
          <img src="https://images.unsplash.com/photo-1575751639353-e292e76daca3?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80" class="d-block w-100" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h5>La Ghalib E IllAllah</h5>
            <p>There is no God but Allah.</p>
          </div>
        </div>
        <div class="carousel-item">
          <img src="https://images.unsplash.com/photo-1487800940032-1cf211187aea?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1169&q=80" class="d-block w-100" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h5>Remember Him and He will remember you</h5>
            <p>Why ask someone else when He is there?.</p>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>';
}

add_action('wp_enqueue_scripts', 'enqueue_script');
add_action('admin_enqueue_scripts', 'enqueue_style');

function enqueue_script()
{
  wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'assets/js/random-user.js', array('jquery'), '1.0.0', true);
}

function enqueue_style()
{
  wp_enqueue_style('custom-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
}

add_action('wp_body_open', 'add_modal');

function add_modal()
{
  echo '
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">*** Random User ***</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card" style="width: 29rem;">
                        <div class="card-body">
                        <div class="row">
                        <div class="col">
                            <img id="picture"></img>
                            <p id="gender"></p>
                            <p id="title"></p>
                            <p id="first"></p>
                            <p id="last"></p>
                            <p id="dob"></p>
                        </div>
                        <div class="col">
                            <p id="age"></p>
                            <p id="email"></p>
                            <p id="streetNo"></p>
                            <p id="streetName"></p>
                            <p id="city"></p>
                            <p id="state"></p>
                            <p id="phone"></p>
                        </div>
                    </div>
                            <a id="again" class="btn btn-primary">New Data</a>
                            &nbsp;
                            Male <input type="checkbox" id="male">
                            &nbsp;
                            Female <input type="checkbox" id="female">
                            &nbsp;
                            <label for="country">Country</label>
                            <select id="country" name="country">
                            <option value="Random">Random</option>
                              <option value="UK">UK</option>
                              <option value="US">US</option>
                              <option value="AS">AS</option>
                              <option value="NZ">NZ</option>
                              <option value="AU">AU</option>
                              <option value="BR">BR</option>
                              <option value="CA">CA</option>
                              <option value="CH">CH</option>
                              <option value="DE">DE</option>
                              <option value="DK">DK</option>
                              <option value="ES">ES</option>
                              <option value="FI">FI</option>
                              <option value="FR">FR</option>
                              <option value="GB">GB</option>
                              <option value="IE">IE</option>
                              <option value="IN">IN</option>
                              <option value="IR">IR</option>
                              <option value="MX">MX</option>
                              <option value="NL">NL</option>
                              <option value="NO">NO</option>
                              <option value="NZ">NZ</option>
                              <option value="RS">RS</option>
                              <option value="TR">TR</option>
                              <option value="UA">UA</option>

                            </select>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    ';

  echo '<style>
    .round-button {
      display: inline-block;
      border-radius: 50%;
      background-color: #ccc;
      color: #fff;
      padding: 30px 10px;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
    }
    
    .round-button:hover {
      background-color: #aaa;
    }
    </style>';

  echo '
    <div id="btn-modal" type="button" class="round-button" data-bs-toggle="modal" data-bs-target="#myModal">Get Data</div>
    ';
}
wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.2', true);
