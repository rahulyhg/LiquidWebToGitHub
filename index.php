<!DOCTYPE html>
<html lang="en">
<head>

  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title>Black Bear Energy</title>
  <meta name="description" content="">
  <meta name="author" content="">

  <!-- Mobile Specific Metas
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FONT
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="portal/css/normalize.css">
  <link rel="stylesheet" href="portal/css/skeleton.css">
  <link rel="stylesheet" href="portal/css/style.css">

  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="icon" type="image/png" href="portal/images/favicon.ico">

  <style>
    html {background:#464a63;}
  </style>
</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container">



    <div class="row" style="margin-top: 10%;">
      <div class="two columns offset-by-five">
        <img src='portal/images/logo.png' style='margin:0 auto;width:100%;'>
      </div>
      <div class="four columns offset-by-four">
        <img src='portal/images/logotitle.png' style='margin:0 auto; width:100%;'>
      </div>
    </div>


    <div class="row" id='login'>
      <div class="six columns offset-by-three login">
        <form style='margin-bottom: .1rem;'>
          <div class="row">
            <div class="twelve columns" style="margin-bottom: 1rem;">Sign In</div>
          </div>
          <input class="u-full-width form-material" type="email" placeholder="Email Address">
          <input class="u-full-width form-material" type="password" placeholder="Password">
          <div class="row" style='margin-bottom: 2rem;'>
            <div class="six columns">
              <label class="u-pull-left checkbox-container">
                <input type="checkbox" class='form-material'>
                <span class="checkmark"></span>
                <span class="label-body">Remember Me</span>
              </label>
            </div>
            <div class="six columns">
              <a class="u-pull-right" href="portal/forgot.php">Forgot Password?</a>
            </div>
          </div>
          <input class="button-primary u-full-width" type="submit" value="Log In">
        </form>
        <div class="row">
          <div class="twelve columns">
            <span class="u-pull-left">Dont have and account? <a href="#signup" class='magenta'> Sign up</a></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row" id="signup">
      <div class="twelve columns signup">
        <form>
          <div class="row">
            <div class="twelve columns" style="margin-bottom: 1rem;">Sign Up</div>
          </div>
          <div class="row">
            <div class="six columns">
              <input class="u-full-width form-material" type="text" placeholder="First Name">
            </div>
            <div class="six columns">
              <input class="u-full-width form-material" type="text" placeholder="Last Name">
            </div>
          </div>
          <div class="row">
            <div class="six columns">
              <input class="u-full-width form-material" type="text" placeholder="Company Name">
            </div>
            <div class="six columns">
              <input class="u-full-width form-material" type="tel" placeholder="Phone Number">
            </div>
          </div>
          <div class="row">
            <div class="six columns">
              <input class="u-full-width form-material" type="text" placeholder="Account Type">
            </div>
            <div class="six columns">
              <input class="u-full-width form-material" type="email" placeholder="Email Address">
            </div>
          </div>
          <div class="row" style='margin-bottom: 2rem;'>
            <div class="six columns">
              <input class="u-full-width form-material" type="password" placeholder="Please Enter Password">
            </div>
            <div class="six columns">
              <input class="u-full-width form-material" type="password" placeholder="Confirm Password">
            </div>
          </div>
          <div class="row">
            <div class="five columns">
              <input class="button-primary u-full-width" type="submit" value="Get Started">
            </div>
          </div>
        </form>
      </div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
</body>
</html>
