{{--
    Country options partial — 195 countries, Indonesia first.
    Usage: @include('partials._country_options', ['selected' => $value])
--}}
@php $sel = $selected ?? ''; @endphp
<option value="">— Pilih Negara —</option>
<option value="Indonesia" {{ $sel === 'Indonesia' ? 'selected' : '' }}>🇮🇩 Indonesia</option>
<option disabled>──────────────</option>
<option value="Afghanistan" {{ $sel === 'Afghanistan' ? 'selected' : '' }}>Afghanistan</option>
<option value="Albania" {{ $sel === 'Albania' ? 'selected' : '' }}>Albania</option>
<option value="Algeria" {{ $sel === 'Algeria' ? 'selected' : '' }}>Algeria</option>
<option value="Andorra" {{ $sel === 'Andorra' ? 'selected' : '' }}>Andorra</option>
<option value="Angola" {{ $sel === 'Angola' ? 'selected' : '' }}>Angola</option>
<option value="Antigua and Barbuda" {{ $sel === 'Antigua and Barbuda' ? 'selected' : '' }}>Antigua and Barbuda</option>
<option value="Argentina" {{ $sel === 'Argentina' ? 'selected' : '' }}>Argentina</option>
<option value="Armenia" {{ $sel === 'Armenia' ? 'selected' : '' }}>Armenia</option>
<option value="Australia" {{ $sel === 'Australia' ? 'selected' : '' }}>Australia</option>
<option value="Austria" {{ $sel === 'Austria' ? 'selected' : '' }}>Austria</option>
<option value="Azerbaijan" {{ $sel === 'Azerbaijan' ? 'selected' : '' }}>Azerbaijan</option>
<option value="Bahamas" {{ $sel === 'Bahamas' ? 'selected' : '' }}>Bahamas</option>
<option value="Bahrain" {{ $sel === 'Bahrain' ? 'selected' : '' }}>Bahrain</option>
<option value="Bangladesh" {{ $sel === 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
<option value="Barbados" {{ $sel === 'Barbados' ? 'selected' : '' }}>Barbados</option>
<option value="Belarus" {{ $sel === 'Belarus' ? 'selected' : '' }}>Belarus</option>
<option value="Belgium" {{ $sel === 'Belgium' ? 'selected' : '' }}>Belgium</option>
<option value="Belize" {{ $sel === 'Belize' ? 'selected' : '' }}>Belize</option>
<option value="Benin" {{ $sel === 'Benin' ? 'selected' : '' }}>Benin</option>
<option value="Bhutan" {{ $sel === 'Bhutan' ? 'selected' : '' }}>Bhutan</option>
<option value="Bolivia" {{ $sel === 'Bolivia' ? 'selected' : '' }}>Bolivia</option>
<option value="Bosnia and Herzegovina" {{ $sel === 'Bosnia and Herzegovina' ? 'selected' : '' }}>Bosnia and Herzegovina</option>
<option value="Botswana" {{ $sel === 'Botswana' ? 'selected' : '' }}>Botswana</option>
<option value="Brazil" {{ $sel === 'Brazil' ? 'selected' : '' }}>Brazil</option>
<option value="Brunei" {{ $sel === 'Brunei' ? 'selected' : '' }}>Brunei</option>
<option value="Bulgaria" {{ $sel === 'Bulgaria' ? 'selected' : '' }}>Bulgaria</option>
<option value="Burkina Faso" {{ $sel === 'Burkina Faso' ? 'selected' : '' }}>Burkina Faso</option>
<option value="Burundi" {{ $sel === 'Burundi' ? 'selected' : '' }}>Burundi</option>
<option value="Cabo Verde" {{ $sel === 'Cabo Verde' ? 'selected' : '' }}>Cabo Verde</option>
<option value="Cambodia" {{ $sel === 'Cambodia' ? 'selected' : '' }}>Cambodia</option>
<option value="Cameroon" {{ $sel === 'Cameroon' ? 'selected' : '' }}>Cameroon</option>
<option value="Canada" {{ $sel === 'Canada' ? 'selected' : '' }}>Canada</option>
<option value="Central African Republic" {{ $sel === 'Central African Republic' ? 'selected' : '' }}>Central African Republic</option>
<option value="Chad" {{ $sel === 'Chad' ? 'selected' : '' }}>Chad</option>
<option value="Chile" {{ $sel === 'Chile' ? 'selected' : '' }}>Chile</option>
<option value="China" {{ $sel === 'China' ? 'selected' : '' }}>China</option>
<option value="Colombia" {{ $sel === 'Colombia' ? 'selected' : '' }}>Colombia</option>
<option value="Comoros" {{ $sel === 'Comoros' ? 'selected' : '' }}>Comoros</option>
<option value="Congo (Brazzaville)" {{ $sel === 'Congo (Brazzaville)' ? 'selected' : '' }}>Congo (Brazzaville)</option>
<option value="Congo (Kinshasa)" {{ $sel === 'Congo (Kinshasa)' ? 'selected' : '' }}>Congo (Kinshasa)</option>
<option value="Costa Rica" {{ $sel === 'Costa Rica' ? 'selected' : '' }}>Costa Rica</option>
<option value="Croatia" {{ $sel === 'Croatia' ? 'selected' : '' }}>Croatia</option>
<option value="Cuba" {{ $sel === 'Cuba' ? 'selected' : '' }}>Cuba</option>
<option value="Cyprus" {{ $sel === 'Cyprus' ? 'selected' : '' }}>Cyprus</option>
<option value="Czech Republic" {{ $sel === 'Czech Republic' ? 'selected' : '' }}>Czech Republic</option>
<option value="Denmark" {{ $sel === 'Denmark' ? 'selected' : '' }}>Denmark</option>
<option value="Djibouti" {{ $sel === 'Djibouti' ? 'selected' : '' }}>Djibouti</option>
<option value="Dominica" {{ $sel === 'Dominica' ? 'selected' : '' }}>Dominica</option>
<option value="Dominican Republic" {{ $sel === 'Dominican Republic' ? 'selected' : '' }}>Dominican Republic</option>
<option value="Ecuador" {{ $sel === 'Ecuador' ? 'selected' : '' }}>Ecuador</option>
<option value="Egypt" {{ $sel === 'Egypt' ? 'selected' : '' }}>Egypt</option>
<option value="El Salvador" {{ $sel === 'El Salvador' ? 'selected' : '' }}>El Salvador</option>
<option value="Equatorial Guinea" {{ $sel === 'Equatorial Guinea' ? 'selected' : '' }}>Equatorial Guinea</option>
<option value="Eritrea" {{ $sel === 'Eritrea' ? 'selected' : '' }}>Eritrea</option>
<option value="Estonia" {{ $sel === 'Estonia' ? 'selected' : '' }}>Estonia</option>
<option value="Eswatini" {{ $sel === 'Eswatini' ? 'selected' : '' }}>Eswatini</option>
<option value="Ethiopia" {{ $sel === 'Ethiopia' ? 'selected' : '' }}>Ethiopia</option>
<option value="Fiji" {{ $sel === 'Fiji' ? 'selected' : '' }}>Fiji</option>
<option value="Finland" {{ $sel === 'Finland' ? 'selected' : '' }}>Finland</option>
<option value="France" {{ $sel === 'France' ? 'selected' : '' }}>France</option>
<option value="Gabon" {{ $sel === 'Gabon' ? 'selected' : '' }}>Gabon</option>
<option value="Gambia" {{ $sel === 'Gambia' ? 'selected' : '' }}>Gambia</option>
<option value="Georgia" {{ $sel === 'Georgia' ? 'selected' : '' }}>Georgia</option>
<option value="Germany" {{ $sel === 'Germany' ? 'selected' : '' }}>Germany</option>
<option value="Ghana" {{ $sel === 'Ghana' ? 'selected' : '' }}>Ghana</option>
<option value="Greece" {{ $sel === 'Greece' ? 'selected' : '' }}>Greece</option>
<option value="Grenada" {{ $sel === 'Grenada' ? 'selected' : '' }}>Grenada</option>
<option value="Guatemala" {{ $sel === 'Guatemala' ? 'selected' : '' }}>Guatemala</option>
<option value="Guinea" {{ $sel === 'Guinea' ? 'selected' : '' }}>Guinea</option>
<option value="Guinea-Bissau" {{ $sel === 'Guinea-Bissau' ? 'selected' : '' }}>Guinea-Bissau</option>
<option value="Guyana" {{ $sel === 'Guyana' ? 'selected' : '' }}>Guyana</option>
<option value="Haiti" {{ $sel === 'Haiti' ? 'selected' : '' }}>Haiti</option>
<option value="Honduras" {{ $sel === 'Honduras' ? 'selected' : '' }}>Honduras</option>
<option value="Hungary" {{ $sel === 'Hungary' ? 'selected' : '' }}>Hungary</option>
<option value="Iceland" {{ $sel === 'Iceland' ? 'selected' : '' }}>Iceland</option>
<option value="India" {{ $sel === 'India' ? 'selected' : '' }}>India</option>
<option value="Iran" {{ $sel === 'Iran' ? 'selected' : '' }}>Iran</option>
<option value="Iraq" {{ $sel === 'Iraq' ? 'selected' : '' }}>Iraq</option>
<option value="Ireland" {{ $sel === 'Ireland' ? 'selected' : '' }}>Ireland</option>
<option value="Israel" {{ $sel === 'Israel' ? 'selected' : '' }}>Israel</option>
<option value="Italy" {{ $sel === 'Italy' ? 'selected' : '' }}>Italy</option>
<option value="Jamaica" {{ $sel === 'Jamaica' ? 'selected' : '' }}>Jamaica</option>
<option value="Japan" {{ $sel === 'Japan' ? 'selected' : '' }}>Japan</option>
<option value="Jordan" {{ $sel === 'Jordan' ? 'selected' : '' }}>Jordan</option>
<option value="Kazakhstan" {{ $sel === 'Kazakhstan' ? 'selected' : '' }}>Kazakhstan</option>
<option value="Kenya" {{ $sel === 'Kenya' ? 'selected' : '' }}>Kenya</option>
<option value="Kiribati" {{ $sel === 'Kiribati' ? 'selected' : '' }}>Kiribati</option>
<option value="Kuwait" {{ $sel === 'Kuwait' ? 'selected' : '' }}>Kuwait</option>
<option value="Kyrgyzstan" {{ $sel === 'Kyrgyzstan' ? 'selected' : '' }}>Kyrgyzstan</option>
<option value="Laos" {{ $sel === 'Laos' ? 'selected' : '' }}>Laos</option>
<option value="Latvia" {{ $sel === 'Latvia' ? 'selected' : '' }}>Latvia</option>
<option value="Lebanon" {{ $sel === 'Lebanon' ? 'selected' : '' }}>Lebanon</option>
<option value="Lesotho" {{ $sel === 'Lesotho' ? 'selected' : '' }}>Lesotho</option>
<option value="Liberia" {{ $sel === 'Liberia' ? 'selected' : '' }}>Liberia</option>
<option value="Libya" {{ $sel === 'Libya' ? 'selected' : '' }}>Libya</option>
<option value="Liechtenstein" {{ $sel === 'Liechtenstein' ? 'selected' : '' }}>Liechtenstein</option>
<option value="Lithuania" {{ $sel === 'Lithuania' ? 'selected' : '' }}>Lithuania</option>
<option value="Luxembourg" {{ $sel === 'Luxembourg' ? 'selected' : '' }}>Luxembourg</option>
<option value="Madagascar" {{ $sel === 'Madagascar' ? 'selected' : '' }}>Madagascar</option>
<option value="Malawi" {{ $sel === 'Malawi' ? 'selected' : '' }}>Malawi</option>
<option value="Malaysia" {{ $sel === 'Malaysia' ? 'selected' : '' }}>Malaysia</option>
<option value="Maldives" {{ $sel === 'Maldives' ? 'selected' : '' }}>Maldives</option>
<option value="Mali" {{ $sel === 'Mali' ? 'selected' : '' }}>Mali</option>
<option value="Malta" {{ $sel === 'Malta' ? 'selected' : '' }}>Malta</option>
<option value="Marshall Islands" {{ $sel === 'Marshall Islands' ? 'selected' : '' }}>Marshall Islands</option>
<option value="Mauritania" {{ $sel === 'Mauritania' ? 'selected' : '' }}>Mauritania</option>
<option value="Mauritius" {{ $sel === 'Mauritius' ? 'selected' : '' }}>Mauritius</option>
<option value="Mexico" {{ $sel === 'Mexico' ? 'selected' : '' }}>Mexico</option>
<option value="Micronesia" {{ $sel === 'Micronesia' ? 'selected' : '' }}>Micronesia</option>
<option value="Moldova" {{ $sel === 'Moldova' ? 'selected' : '' }}>Moldova</option>
<option value="Monaco" {{ $sel === 'Monaco' ? 'selected' : '' }}>Monaco</option>
<option value="Mongolia" {{ $sel === 'Mongolia' ? 'selected' : '' }}>Mongolia</option>
<option value="Montenegro" {{ $sel === 'Montenegro' ? 'selected' : '' }}>Montenegro</option>
<option value="Morocco" {{ $sel === 'Morocco' ? 'selected' : '' }}>Morocco</option>
<option value="Mozambique" {{ $sel === 'Mozambique' ? 'selected' : '' }}>Mozambique</option>
<option value="Myanmar" {{ $sel === 'Myanmar' ? 'selected' : '' }}>Myanmar</option>
<option value="Namibia" {{ $sel === 'Namibia' ? 'selected' : '' }}>Namibia</option>
<option value="Nauru" {{ $sel === 'Nauru' ? 'selected' : '' }}>Nauru</option>
<option value="Nepal" {{ $sel === 'Nepal' ? 'selected' : '' }}>Nepal</option>
<option value="Netherlands" {{ $sel === 'Netherlands' ? 'selected' : '' }}>Netherlands</option>
<option value="New Zealand" {{ $sel === 'New Zealand' ? 'selected' : '' }}>New Zealand</option>
<option value="Nicaragua" {{ $sel === 'Nicaragua' ? 'selected' : '' }}>Nicaragua</option>
<option value="Niger" {{ $sel === 'Niger' ? 'selected' : '' }}>Niger</option>
<option value="Nigeria" {{ $sel === 'Nigeria' ? 'selected' : '' }}>Nigeria</option>
<option value="North Korea" {{ $sel === 'North Korea' ? 'selected' : '' }}>North Korea</option>
<option value="North Macedonia" {{ $sel === 'North Macedonia' ? 'selected' : '' }}>North Macedonia</option>
<option value="Norway" {{ $sel === 'Norway' ? 'selected' : '' }}>Norway</option>
<option value="Oman" {{ $sel === 'Oman' ? 'selected' : '' }}>Oman</option>
<option value="Pakistan" {{ $sel === 'Pakistan' ? 'selected' : '' }}>Pakistan</option>
<option value="Palau" {{ $sel === 'Palau' ? 'selected' : '' }}>Palau</option>
<option value="Palestine" {{ $sel === 'Palestine' ? 'selected' : '' }}>Palestine</option>
<option value="Panama" {{ $sel === 'Panama' ? 'selected' : '' }}>Panama</option>
<option value="Papua New Guinea" {{ $sel === 'Papua New Guinea' ? 'selected' : '' }}>Papua New Guinea</option>
<option value="Paraguay" {{ $sel === 'Paraguay' ? 'selected' : '' }}>Paraguay</option>
<option value="Peru" {{ $sel === 'Peru' ? 'selected' : '' }}>Peru</option>
<option value="Philippines" {{ $sel === 'Philippines' ? 'selected' : '' }}>Philippines</option>
<option value="Poland" {{ $sel === 'Poland' ? 'selected' : '' }}>Poland</option>
<option value="Portugal" {{ $sel === 'Portugal' ? 'selected' : '' }}>Portugal</option>
<option value="Qatar" {{ $sel === 'Qatar' ? 'selected' : '' }}>Qatar</option>
<option value="Romania" {{ $sel === 'Romania' ? 'selected' : '' }}>Romania</option>
<option value="Russia" {{ $sel === 'Russia' ? 'selected' : '' }}>Russia</option>
<option value="Rwanda" {{ $sel === 'Rwanda' ? 'selected' : '' }}>Rwanda</option>
<option value="Saint Kitts and Nevis" {{ $sel === 'Saint Kitts and Nevis' ? 'selected' : '' }}>Saint Kitts and Nevis</option>
<option value="Saint Lucia" {{ $sel === 'Saint Lucia' ? 'selected' : '' }}>Saint Lucia</option>
<option value="Saint Vincent and the Grenadines" {{ $sel === 'Saint Vincent and the Grenadines' ? 'selected' : '' }}>Saint Vincent and the Grenadines</option>
<option value="Samoa" {{ $sel === 'Samoa' ? 'selected' : '' }}>Samoa</option>
<option value="San Marino" {{ $sel === 'San Marino' ? 'selected' : '' }}>San Marino</option>
<option value="Sao Tome and Principe" {{ $sel === 'Sao Tome and Principe' ? 'selected' : '' }}>Sao Tome and Principe</option>
<option value="Saudi Arabia" {{ $sel === 'Saudi Arabia' ? 'selected' : '' }}>Saudi Arabia</option>
<option value="Senegal" {{ $sel === 'Senegal' ? 'selected' : '' }}>Senegal</option>
<option value="Serbia" {{ $sel === 'Serbia' ? 'selected' : '' }}>Serbia</option>
<option value="Seychelles" {{ $sel === 'Seychelles' ? 'selected' : '' }}>Seychelles</option>
<option value="Sierra Leone" {{ $sel === 'Sierra Leone' ? 'selected' : '' }}>Sierra Leone</option>
<option value="Singapore" {{ $sel === 'Singapore' ? 'selected' : '' }}>Singapore</option>
<option value="Slovakia" {{ $sel === 'Slovakia' ? 'selected' : '' }}>Slovakia</option>
<option value="Slovenia" {{ $sel === 'Slovenia' ? 'selected' : '' }}>Slovenia</option>
<option value="Solomon Islands" {{ $sel === 'Solomon Islands' ? 'selected' : '' }}>Solomon Islands</option>
<option value="Somalia" {{ $sel === 'Somalia' ? 'selected' : '' }}>Somalia</option>
<option value="South Africa" {{ $sel === 'South Africa' ? 'selected' : '' }}>South Africa</option>
<option value="South Korea" {{ $sel === 'South Korea' ? 'selected' : '' }}>South Korea</option>
<option value="South Sudan" {{ $sel === 'South Sudan' ? 'selected' : '' }}>South Sudan</option>
<option value="Spain" {{ $sel === 'Spain' ? 'selected' : '' }}>Spain</option>
<option value="Sri Lanka" {{ $sel === 'Sri Lanka' ? 'selected' : '' }}>Sri Lanka</option>
<option value="Sudan" {{ $sel === 'Sudan' ? 'selected' : '' }}>Sudan</option>
<option value="Suriname" {{ $sel === 'Suriname' ? 'selected' : '' }}>Suriname</option>
<option value="Sweden" {{ $sel === 'Sweden' ? 'selected' : '' }}>Sweden</option>
<option value="Switzerland" {{ $sel === 'Switzerland' ? 'selected' : '' }}>Switzerland</option>
<option value="Syria" {{ $sel === 'Syria' ? 'selected' : '' }}>Syria</option>
<option value="Taiwan" {{ $sel === 'Taiwan' ? 'selected' : '' }}>Taiwan</option>
<option value="Tajikistan" {{ $sel === 'Tajikistan' ? 'selected' : '' }}>Tajikistan</option>
<option value="Tanzania" {{ $sel === 'Tanzania' ? 'selected' : '' }}>Tanzania</option>
<option value="Thailand" {{ $sel === 'Thailand' ? 'selected' : '' }}>Thailand</option>
<option value="Timor-Leste" {{ $sel === 'Timor-Leste' ? 'selected' : '' }}>Timor-Leste</option>
<option value="Togo" {{ $sel === 'Togo' ? 'selected' : '' }}>Togo</option>
<option value="Tonga" {{ $sel === 'Tonga' ? 'selected' : '' }}>Tonga</option>
<option value="Trinidad and Tobago" {{ $sel === 'Trinidad and Tobago' ? 'selected' : '' }}>Trinidad and Tobago</option>
<option value="Tunisia" {{ $sel === 'Tunisia' ? 'selected' : '' }}>Tunisia</option>
<option value="Turkey" {{ $sel === 'Turkey' ? 'selected' : '' }}>Turkey</option>
<option value="Turkmenistan" {{ $sel === 'Turkmenistan' ? 'selected' : '' }}>Turkmenistan</option>
<option value="Tuvalu" {{ $sel === 'Tuvalu' ? 'selected' : '' }}>Tuvalu</option>
<option value="Uganda" {{ $sel === 'Uganda' ? 'selected' : '' }}>Uganda</option>
<option value="Ukraine" {{ $sel === 'Ukraine' ? 'selected' : '' }}>Ukraine</option>
<option value="United Arab Emirates" {{ $sel === 'United Arab Emirates' ? 'selected' : '' }}>United Arab Emirates</option>
<option value="United Kingdom" {{ $sel === 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
<option value="United States" {{ $sel === 'United States' ? 'selected' : '' }}>United States</option>
<option value="Uruguay" {{ $sel === 'Uruguay' ? 'selected' : '' }}>Uruguay</option>
<option value="Uzbekistan" {{ $sel === 'Uzbekistan' ? 'selected' : '' }}>Uzbekistan</option>
<option value="Vanuatu" {{ $sel === 'Vanuatu' ? 'selected' : '' }}>Vanuatu</option>
<option value="Vatican City" {{ $sel === 'Vatican City' ? 'selected' : '' }}>Vatican City</option>
<option value="Venezuela" {{ $sel === 'Venezuela' ? 'selected' : '' }}>Venezuela</option>
<option value="Vietnam" {{ $sel === 'Vietnam' ? 'selected' : '' }}>Vietnam</option>
<option value="Yemen" {{ $sel === 'Yemen' ? 'selected' : '' }}>Yemen</option>
<option value="Zambia" {{ $sel === 'Zambia' ? 'selected' : '' }}>Zambia</option>
<option value="Zimbabwe" {{ $sel === 'Zimbabwe' ? 'selected' : '' }}>Zimbabwe</option>
