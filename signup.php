<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Sign Up - Bitcoin Giftcards</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <style>
      body { background: linear-gradient(252deg, #1a938a 0%, rgba(26, 147, 138, 0) 100.44%); min-height: 100vh; }
      .signup-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 8px;
        position: relative;
      }
      .giftcard-particles {
        position: absolute;
        inset: 0;
        width: 100vw;
        height: 100vh;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
      }
      .giftcard-particle {
        position: absolute;
        font-size: 2.2rem;
        opacity: 0.18;
        color: #ffbf3f;
        animation: floatGiftcard 8s infinite linear;
      }
      .giftcard-particle:nth-child(2) { left: 10vw; top: 20vh; color: #1a938a; font-size: 2.8rem; animation-delay: 2s; }
      .giftcard-particle:nth-child(3) { left: 80vw; top: 30vh; color: #ffbf3f; font-size: 2.5rem; animation-delay: 4s; }
      .giftcard-particle:nth-child(4) { left: 50vw; top: 70vh; color: #1a938a; font-size: 2.1rem; animation-delay: 1s; }
      .giftcard-particle:nth-child(5) { left: 30vw; top: 60vh; color: #ffbf3f; font-size: 2.7rem; animation-delay: 3s; }
      @keyframes floatGiftcard {
        0% { transform: translateY(0) scale(1); opacity: 0.18; }
        50% { transform: translateY(-30px) scale(1.1); opacity: 0.28; }
        100% { transform: translateY(0) scale(1); opacity: 0.18; }
      }
      .glass-card {
        background: linear-gradient(120deg, #ffbf3f 0%, #1a938a 100%);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
        border-radius: 22px;
        padding: 40px 32px;
        max-width: 400px;
        width: 100%;
        z-index: 1;
        position: relative;
        color: #19376d;
      }
      .logo-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
      }
      .logo {
        width: 64px;
        height: 64px;
        object-fit: contain;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(35,35,76,0.18);
        background: #fff;
      }
      .text {
        font-size: 2rem;
        color: #fff;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-align: center;
      }
      .subtitle {
        color: #bdbdbd;
        font-size: 1.08rem;
        margin-bottom: 22px;
        text-align: center;
      }
      form {
        margin-top: 18px;
      }
      .field-group {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }
      .field {
        display: flex;
        align-items: flex-end;
        background: rgba(51,51,51,0.22);
        border-radius: 10px;
        padding: 0 0 0 12px;
        position: relative;
        transition: box-shadow 0.2s;
      }
      .field:focus-within {
        box-shadow: 0 0 0 2px #4f46e5;
      }
      .field .fas {
        color: #868686;
        font-size: 20px;
        min-width: 36px;
        text-align: center;
        margin-bottom: 10px;
      }
      .input-wrapper {
        flex: 1;
        position: relative;
        display: flex;
        flex-direction: column;
      }
      .input-wrapper label {
        position: absolute;
        left: 10px;
        top: 18px;
        color: #fff;
        font-size: 1rem;
        pointer-events: none;
        transition: 0.2s cubic-bezier(0.4,0,0.2,1);
      }
      .input-wrapper input,
      .input-wrapper select {
        background: #111 !important;
        color: #fff !important;
        border: none;
        border-bottom: 2px solid #444;
        font-size: 1rem;
        padding: 18px 10px 8px 10px;
        outline: none;
        border-radius: 0;
        transition: border-color 0.2s;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
      }
      .input-wrapper input::placeholder,
      .input-wrapper select::placeholder {
        color: #fff !important;
        opacity: 1;
      }
      .input-wrapper input:-webkit-autofill {
        -webkit-text-fill-color: #fff !important;
        box-shadow: 0 0 0 1000px #111 inset !important;
      }
      .input-wrapper input:-moz-autofill {
        color: #fff !important;
        background: #111 !important;
      }
      .input-wrapper input:focus,
      .input-wrapper select:focus {
        border-bottom: 2px solid #4f46e5;
      }
      .input-wrapper input:focus + label,
      .input-wrapper input:not(:placeholder-shown) + label,
      .input-wrapper select:focus + label,
      .input-wrapper select:not([value=""]) + label {
        top: -8px;
        left: 6px;
        font-size: 0.85rem;
        color: #fff;
        background: rgba(27,27,43,0.92);
        padding: 0 4px;
        border-radius: 4px;
      }
      .input-wrapper input:-webkit-autofill + label {
        top: -8px;
        left: 6px;
        font-size: 0.85rem;
        color: #fff;
        background: rgba(27,27,43,0.92);
        padding: 0 4px;
        border-radius: 4px;
      }
      .error-message {
        color: #ff6b6b;
        font-size: 0.85rem;
        margin-top: 2px;
        min-height: 18px;
      }
      .checkbox-group {
        display: flex;
        align-items: center;
        margin: 18px 0 8px 0;
      }
      .checkbox-group input[type="checkbox"] {
        accent-color: #4f46e5;
        width: 18px;
        height: 18px;
        margin-right: 10px;
      }
      .checkbox-group label {
        color: #bdbdbd;
        font-size: 0.98rem;
      }
      .checkbox-group label a {
        color: #4f46e5;
        text-decoration: underline;
      }
      .checkbox-group label a:hover {
        color: #6a11cb;
      }
      .btn-primary {
        width: 100%;
        background: linear-gradient(90deg, #4f46e5 0%, #6a11cb 100%);
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 14px 0;
        font-size: 1.1rem;
        margin-top: 8px;
        cursor: pointer;
        transition: background 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 8px rgba(79,70,229,0.08);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      }
      .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }
      .btn-primary .spinner {
        width: 18px;
        height: 18px;
        border: 3px solid #fff;
        border-top: 3px solid #4f46e5;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        display: none;
      }
      .btn-primary.loading .spinner {
        display: inline-block;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 18px 0 8px 0;
      }
      .divider span {
        color: #bdbdbd;
        font-size: 0.95rem;
        padding: 0 12px;
        background: rgba(27,27,43,0.92);
        border-radius: 4px;
      }
      .divider:before, .divider:after {
        content: '';
        flex: 1;
        height: 1px;
        background: #444;
      }
      .link {
        text-align: center;
        margin-top: 8px;
        color: #fff;
      }
      .link a {
        color: #4f46e5;
        text-decoration: underline;
        font-weight: 500;
      }
      .link a:hover {
        color: #6a11cb;
      }
      /* Modal styles */
      .modal-bg {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
      }
      .modal-bg.active { display: flex; }
      .modal-content {
        background: rgba(27, 27, 43, 0.98);
        border-radius: 22px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        padding: 40px 32px;
        max-width: 350px;
        width: 100%;
        text-align: center;
        color: #fff;
        animation: fadeInUp 0.5s cubic-bezier(0.23, 1, 0.32, 1);
      }
      .modal-content h2 {
        font-size: 2rem;
        margin-bottom: 12px;
        color: #4f46e5;
      }
      .modal-content p {
        font-size: 1.1rem;
        margin-bottom: 0;
      }
      @media (max-width: 600px) {
        .glass-card {
          padding: 32px 8px 24px;
          max-width: 98vw;
        }
        .logo {
          width: 48px;
          height: 48px;
        }
        .text {
          font-size: 1.4rem;
        }
      }
    </style>
  </head>
  <body>
    <div class="signup-container">
      <div class="giftcard-particles">
        <i class="fas fa-giftcard giftcard-particle"></i>
        <i class="fas fa-credit-card giftcard-particle"></i>
        <i class="fas fa-gift giftcard-particle"></i>
        <i class="fas fa-money-bill-wave giftcard-particle"></i>
      </div>
      <div class="glass-card">
        <div class="logo-container">
          <img src="images/logo.png" alt="Bitcoin Giftcards Logo" class="logo">
        </div>
        <div class="text">Create Your Account</div>
        <div class="subtitle">Welcome! Please fill in the details to sign up.</div>
        <form method="post" action="signup.php" autocomplete="off" novalidate id="signupForm">
          <div class="field-group">
            <div class="field">
              <div class="fas fa-user"></div>
              <div class="input-wrapper">
                <input type="text" name="fullname" id="fullname" required placeholder=" " autocomplete="name">
                <label for="fullname">Full Name</label>
                <span class="error-message" id="fullname-error"></span>
              </div>
            </div>
            <div class="field">
              <div class="fas fa-envelope"></div>
              <div class="input-wrapper">
                <input type="email" name="email" id="email" required placeholder=" " autocomplete="email">
                <label for="email">Email</label>
                <span class="error-message" id="email-error"></span>
              </div>
            </div>
            <div class="field">
              <div class="fas fa-phone"></div>
              <div class="input-wrapper">
                <input type="text" name="phone" id="phone" required placeholder=" " autocomplete="tel">
                <label for="phone">Phone</label>
                <span class="error-message" id="phone-error"></span>
              </div>
            </div>
            <div class="field">
              <div class="fas fa-globe"></div>
              <div class="input-wrapper">
                <select name="country" id="country" required>
                  <option value="" disabled selected hidden></option>
                  <option value="AF">Afghanistan</option>
                  <option value="AL">Albania</option>
                  <option value="DZ">Algeria</option>
                  <option value="AS">American Samoa</option>
                  <option value="AD">Andorra</option>
                  <option value="AO">Angola</option>
                  <option value="AI">Anguilla</option>
                  <option value="AQ">Antarctica</option>
                  <option value="AG">Antigua and Barbuda</option>
                  <option value="AR">Argentina</option>
                  <option value="AM">Armenia</option>
                  <option value="AW">Aruba</option>
                  <option value="AU">Australia</option>
                  <option value="AT">Austria</option>
                  <option value="AZ">Azerbaijan</option>
                  <option value="BS">Bahamas</option>
                  <option value="BH">Bahrain</option>
                  <option value="BD">Bangladesh</option>
                  <option value="BB">Barbados</option>
                  <option value="BY">Belarus</option>
                  <option value="BE">Belgium</option>
                  <option value="BZ">Belize</option>
                  <option value="BJ">Benin</option>
                  <option value="BM">Bermuda</option>
                  <option value="BT">Bhutan</option>
                  <option value="BO">Bolivia</option>
                  <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
                  <option value="BA">Bosnia and Herzegovina</option>
                  <option value="BW">Botswana</option>
                  <option value="BV">Bouvet Island</option>
                  <option value="BR">Brazil</option>
                  <option value="IO">British Indian Ocean Territory</option>
                  <option value="BN">Brunei Darussalam</option>
                  <option value="BG">Bulgaria</option>
                  <option value="BF">Burkina Faso</option>
                  <option value="BI">Burundi</option>
                  <option value="CV">Cabo Verde</option>
                  <option value="KH">Cambodia</option>
                  <option value="CM">Cameroon</option>
                  <option value="CA">Canada</option>
                  <option value="KY">Cayman Islands</option>
                  <option value="CF">Central African Republic</option>
                  <option value="TD">Chad</option>
                  <option value="CL">Chile</option>
                  <option value="CN">China</option>
                  <option value="CX">Christmas Island</option>
                  <option value="CC">Cocos (Keeling) Islands</option>
                  <option value="CO">Colombia</option>
                  <option value="KM">Comoros</option>
                  <option value="CG">Congo</option>
                  <option value="CD">Congo, Democratic Republic of the</option>
                  <option value="CK">Cook Islands</option>
                  <option value="CR">Costa Rica</option>
                  <option value="CI">Côte d'Ivoire</option>
                  <option value="HR">Croatia</option>
                  <option value="CU">Cuba</option>
                  <option value="CW">Curaçao</option>
                  <option value="CY">Cyprus</option>
                  <option value="CZ">Czechia</option>
                  <option value="DK">Denmark</option>
                  <option value="DJ">Djibouti</option>
                  <option value="DM">Dominica</option>
                  <option value="DO">Dominican Republic</option>
                  <option value="EC">Ecuador</option>
                  <option value="EG">Egypt</option>
                  <option value="SV">El Salvador</option>
                  <option value="GQ">Equatorial Guinea</option>
                  <option value="ER">Eritrea</option>
                  <option value="EE">Estonia</option>
                  <option value="SZ">Eswatini</option>
                  <option value="ET">Ethiopia</option>
                  <option value="FK">Falkland Islands (Malvinas)</option>
                  <option value="FO">Faroe Islands</option>
                  <option value="FJ">Fiji</option>
                  <option value="FI">Finland</option>
                  <option value="FR">France</option>
                  <option value="GF">French Guiana</option>
                  <option value="PF">French Polynesia</option>
                  <option value="TF">French Southern Territories</option>
                  <option value="GA">Gabon</option>
                  <option value="GM">Gambia</option>
                  <option value="GE">Georgia</option>
                  <option value="DE">Germany</option>
                  <option value="GH">Ghana</option>
                  <option value="GI">Gibraltar</option>
                  <option value="GR">Greece</option>
                  <option value="GL">Greenland</option>
                  <option value="GD">Grenada</option>
                  <option value="GP">Guadeloupe</option>
                  <option value="GU">Guam</option>
                  <option value="GT">Guatemala</option>
                  <option value="GG">Guernsey</option>
                  <option value="GN">Guinea</option>
                  <option value="GW">Guinea-Bissau</option>
                  <option value="GY">Guyana</option>
                  <option value="HT">Haiti</option>
                  <option value="HM">Heard Island and McDonald Islands</option>
                  <option value="VA">Holy See</option>
                  <option value="HN">Honduras</option>
                  <option value="HK">Hong Kong</option>
                  <option value="HU">Hungary</option>
                  <option value="IS">Iceland</option>
                  <option value="IN">India</option>
                  <option value="ID">Indonesia</option>
                  <option value="IR">Iran</option>
                  <option value="IQ">Iraq</option>
                  <option value="IE">Ireland</option>
                  <option value="IM">Isle of Man</option>
                  <option value="IL">Israel</option>
                  <option value="IT">Italy</option>
                  <option value="JM">Jamaica</option>
                  <option value="JP">Japan</option>
                  <option value="JE">Jersey</option>
                  <option value="JO">Jordan</option>
                  <option value="KZ">Kazakhstan</option>
                  <option value="KE">Kenya</option>
                  <option value="KI">Kiribati</option>
                  <option value="KP">Korea (Democratic People's Republic of)</option>
                  <option value="KR">Korea (Republic of)</option>
                  <option value="KW">Kuwait</option>
                  <option value="KG">Kyrgyzstan</option>
                  <option value="LA">Lao People's Democratic Republic</option>
                  <option value="LV">Latvia</option>
                  <option value="LB">Lebanon</option>
                  <option value="LS">Lesotho</option>
                  <option value="LR">Liberia</option>
                  <option value="LY">Libya</option>
                  <option value="LI">Liechtenstein</option>
                  <option value="LT">Lithuania</option>
                  <option value="LU">Luxembourg</option>
                  <option value="MO">Macao</option>
                  <option value="MG">Madagascar</option>
                  <option value="MW">Malawi</option>
                  <option value="MY">Malaysia</option>
                  <option value="MV">Maldives</option>
                  <option value="ML">Mali</option>
                  <option value="MT">Malta</option>
                  <option value="MH">Marshall Islands</option>
                  <option value="MQ">Martinique</option>
                  <option value="MR">Mauritania</option>
                  <option value="MU">Mauritius</option>
                  <option value="YT">Mayotte</option>
                  <option value="MX">Mexico</option>
                  <option value="FM">Micronesia (Federated States of)</option>
                  <option value="MD">Moldova</option>
                  <option value="MC">Monaco</option>
                  <option value="MN">Mongolia</option>
                  <option value="ME">Montenegro</option>
                  <option value="MS">Montserrat</option>
                  <option value="MA">Morocco</option>
                  <option value="MZ">Mozambique</option>
                  <option value="MM">Myanmar</option>
                  <option value="NA">Namibia</option>
                  <option value="NR">Nauru</option>
                  <option value="NP">Nepal</option>
                  <option value="NL">Netherlands</option>
                  <option value="NC">New Caledonia</option>
                  <option value="NZ">New Zealand</option>
                  <option value="NI">Nicaragua</option>
                  <option value="NE">Niger</option>
                  <option value="NG">Nigeria</option>
                  <option value="NU">Niue</option>
                  <option value="NF">Norfolk Island</option>
                  <option value="MK">North Macedonia</option>
                  <option value="MP">Northern Mariana Islands</option>
                  <option value="NO">Norway</option>
                  <option value="OM">Oman</option>
                  <option value="PK">Pakistan</option>
                  <option value="PW">Palau</option>
                  <option value="PS">Palestine, State of</option>
                  <option value="PA">Panama</option>
                  <option value="PG">Papua New Guinea</option>
                  <option value="PY">Paraguay</option>
                  <option value="PE">Peru</option>
                  <option value="PH">Philippines</option>
                  <option value="PN">Pitcairn</option>
                  <option value="PL">Poland</option>
                  <option value="PT">Portugal</option>
                  <option value="PR">Puerto Rico</option>
                  <option value="QA">Qatar</option>
                  <option value="RE">Réunion</option>
                  <option value="RO">Romania</option>
                  <option value="RU">Russia</option>
                  <option value="RW">Rwanda</option>
                  <option value="BL">Saint Barthélemy</option>
                  <option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
                  <option value="KN">Saint Kitts and Nevis</option>
                  <option value="LC">Saint Lucia</option>
                  <option value="MF">Saint Martin (French part)</option>
                  <option value="PM">Saint Pierre and Miquelon</option>
                  <option value="VC">Saint Vincent and the Grenadines</option>
                  <option value="WS">Samoa</option>
                  <option value="SM">San Marino</option>
                  <option value="ST">Sao Tome and Principe</option>
                  <option value="SA">Saudi Arabia</option>
                  <option value="SN">Senegal</option>
                  <option value="RS">Serbia</option>
                  <option value="SC">Seychelles</option>
                  <option value="SL">Sierra Leone</option>
                  <option value="SG">Singapore</option>
                  <option value="SX">Sint Maarten (Dutch part)</option>
                  <option value="SK">Slovakia</option>
                  <option value="SI">Slovenia</option>
                  <option value="SB">Solomon Islands</option>
                  <option value="SO">Somalia</option>
                  <option value="ZA">South Africa</option>
                  <option value="GS">South Georgia and the South Sandwich Islands</option>
                  <option value="SS">South Sudan</option>
                  <option value="ES">Spain</option>
                  <option value="LK">Sri Lanka</option>
                  <option value="SD">Sudan</option>
                  <option value="SR">Suriname</option>
                  <option value="SJ">Svalbard and Jan Mayen</option>
                  <option value="SE">Sweden</option>
                  <option value="CH">Switzerland</option>
                  <option value="SY">Syrian Arab Republic</option>
                  <option value="TW">Taiwan</option>
                  <option value="TJ">Tajikistan</option>
                  <option value="TZ">Tanzania</option>
                  <option value="TH">Thailand</option>
                  <option value="TL">Timor-Leste</option>
                  <option value="TG">Togo</option>
                  <option value="TK">Tokelau</option>
                  <option value="TO">Tonga</option>
                  <option value="TT">Trinidad and Tobago</option>
                  <option value="TN">Tunisia</option>
                  <option value="TR">Turkey</option>
                  <option value="TM">Turkmenistan</option>
                  <option value="TC">Turks and Caicos Islands</option>
                  <option value="TV">Tuvalu</option>
                  <option value="UG">Uganda</option>
                  <option value="UA">Ukraine</option>
                  <option value="AE">United Arab Emirates</option>
                  <option value="GB">United Kingdom</option>
                  <option value="US">United States</option>
                  <option value="UM">United States Minor Outlying Islands</option>
                  <option value="UY">Uruguay</option>
                  <option value="UZ">Uzbekistan</option>
                  <option value="VU">Vanuatu</option>
                  <option value="VE">Venezuela</option>
                  <option value="VN">Viet Nam</option>
                  <option value="VG">Virgin Islands (British)</option>
                  <option value="VI">Virgin Islands (U.S.)</option>
                  <option value="WF">Wallis and Futuna</option>
                  <option value="EH">Western Sahara</option>
                  <option value="YE">Yemen</option>
                  <option value="ZM">Zambia</option>
                  <option value="ZW">Zimbabwe</option>
                </select>
                <label for="country">Country</label>
                <span class="error-message" id="country-error"></span>
              </div>
            </div>
            <div class="field">
              <div class="fas fa-user-circle"></div>
              <div class="input-wrapper">
                <input type="text" name="username" id="username" required placeholder=" " autocomplete="username">
                <label for="username">Username</label>
                <span class="error-message" id="username-error"></span>
              </div>
            </div>
            <div class="field">
              <div class="fas fa-lock"></div>
              <div class="input-wrapper">
                <input type="password" name="password" id="password" required placeholder=" " autocomplete="new-password">
                <label for="password">Password</label>
                <span class="error-message" id="password-error"></span>
              </div>
            </div>
            <div class="field">
              <div class="fas fa-lock"></div>
              <div class="input-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" required placeholder=" " autocomplete="new-password">
                <label for="confirm_password">Confirm Password</label>
                <span class="error-message" id="confirm-password-error"></span>
              </div>
            </div>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="terms" id="terms" required>
            <label for="terms">I agree to the <a href="#">terms and conditions</a></label>
            <span class="error-message" id="terms-error"></span>
          </div>
          <button type="submit" class="btn-primary" id="signupBtn">
            <span>Sign Up</span>
            <span class="spinner"></span>
          </button>
          <div class="divider"><span>or</span></div>
          <div class="link">
            Already a member? <a href="login.php">Login now</a>
          </div>
          <div class="back-home-btn" style="text-align:center; margin-top: 24px;">
            <a href="index.html" class="btn-home" style="display:inline-block;padding:12px 32px;font-size:1.1rem;font-weight:600;border:none;border-radius:8px;background:linear-gradient(90deg,#ffbf3f 0%,#1a938a 100%);color:#19376d;text-decoration:none;box-shadow:0 2px 8px rgba(26,147,138,0.08);transition:background 0.2s;">&larr; Back Home</a>
          </div>
        </form>
      </div>
      <!-- Modal for congratulation -->
      <div class="modal-bg" id="congratsModal">
        <div class="modal-content">
          <h2>Congratulations!</h2>
          <p>Your account has been created successfully.<br>Redirecting to login...</p>
        </div>
      </div>
      <script>
        // Button loading spinner UX
        const signupForm = document.getElementById('signupForm');
        const signupBtn = document.getElementById('signupBtn');
        const congratsModal = document.getElementById('congratsModal');
        signupForm.addEventListener('submit', function(e) {
          e.preventDefault();
          signupBtn.classList.add('loading');
          signupBtn.disabled = true;
          // Gather form data
          const formData = new FormData(signupForm);
          // Map field names for backend
          formData.set('name', formData.get('fullname'));
          // AJAX submit
          fetch('auth_register.php', {
            method: 'POST',
            body: formData
          })
          .then(async res => {
            const text = await res.text();
            console.log('Raw response:', text); // Debug log
            try {
              return JSON.parse(text);
            } catch (e) {
              throw new Error('Invalid JSON: ' + text);
            }
          })
          .then(data => {
            signupBtn.classList.remove('loading');
            signupBtn.disabled = false;
            if (data.success) {
              congratsModal.classList.add('active');
              setTimeout(() => {
                window.location.href = 'login.php';
              }, 2500);
            } else {
              // Show error message (reuse error-message span or alert)
              let errorSpan = document.querySelector('.error-message#form-error');
              if (!errorSpan) {
                errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.id = 'form-error';
                signupForm.appendChild(errorSpan);
              }
              errorSpan.textContent = data.message;
              errorSpan.style.display = 'block';
            }
          })
          .catch((err) => {
            signupBtn.classList.remove('loading');
            signupBtn.disabled = false;
            let errorSpan = document.querySelector('.error-message#form-error');
            if (!errorSpan) {
              errorSpan = document.createElement('span');
              errorSpan.className = 'error-message';
              errorSpan.id = 'form-error';
              signupForm.appendChild(errorSpan);
            }
            errorSpan.textContent = err.message || 'An error occurred. Please try again.';
            errorSpan.style.display = 'block';
          });
        });
        // Floating label for select
        const countrySelect = document.getElementById('country');
        countrySelect.addEventListener('change', function() {
          countrySelect.classList.toggle('selected', countrySelect.value !== '');
        });
      </script>
    </div>
  </body>
</html>
