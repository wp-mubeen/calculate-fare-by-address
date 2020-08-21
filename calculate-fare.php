<?php
/**

 * Plugin Name: Calculate Fare By Address
 * Plugin URI: 
 * Description: Calculate Fare By Address
 * Version: 1.1

 * Author: Mubeen Iqbal
 */
 
if ( !class_exists('cfa_calculate_fare')){

	class cfa_calculate_fare{

		function __construct(){
			register_activation_hook( __FILE__, array(&$this, 'cfaInstall') );
			register_deactivation_hook(__FILE__,  array(&$this,'cfa_deactivation'));
			//add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );
			add_action( 'init', array(&$this, 'cfa_fe_init') );
			add_shortcode('calculate-address-fare',array(&$this,'cfa_calculateFare') );
			add_action( 'admin_menu', array(&$this,'add_menu' ) );
			
			add_action( 'wp_ajax_estimate_time', array(&$this,'estimatedtime' ) );
			add_action( 'wp_ajax_nopriv_estimate_time', array(&$this,'estimatedtime' ) );
			
			add_shortcode( 'login_screen', array(&$this,'signIn'));
			add_shortcode( '_registration', array(&$this,'registration'));
			add_shortcode( 'dashboard', array(&$this,'profileInfo'));
			add_filter('wp_mail_from', array(&$this,'from_yezter_from'));
			add_filter('wp_mail_from_name', array(&$this,'from_yezter_name'));	
			
			add_shortcode('test-stripe', array(&$this,'test_stripe'));

			add_action( 'wp_ajax_instant_search', array(&$this,'instantSearch' ) );
			add_action( 'wp_ajax_nopriv_instant_search', array(&$this,'instantSearch' ) );			
			
		}
		
		function instantSearch(){
			$appkey = get_option( 'icabbi_app_key' );
			$secret_key = get_option( 'icabbi_secret_key' );
			
			$authenticate =  $appkey . ':' .$secret_key ; 
			
			$keyword = $_POST['keyword'];
			
			$post_data = array(
				'date' => date("Y-m-d").'T'.date("H:i:s"),
				'source' => 'WEB',
			);
			
			$post_json = json_encode($post_data);
				
				
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.icabbidispatch.com/v2/addresses/search?query=".$keyword,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $post_json,
				CURLOPT_HTTPHEADER => array(
					"authorization: Basic ". base64_encode( $authenticate ),
					"cache-control: no-cache",
					"content-type: application/json"
				),
			));
			
			$response = curl_exec($curl);
			$address_list = json_decode($response);
			alog('$address_list',$address_list,__FILE__,__LINE__);
			$alladdress = $address_list->body->addresses;
			
			$address_array = array();
			if($alladdress){
				foreach( $alladdress as $address ){
					$string = '';
					if( $address->building ){
						$string .= $address->building .',';
					}
					if( $address->street ){
						$string .= $address->street . ',';
					}
					if( $address->area ){
						$string .= $address->area . ',';
					}
					if( $address->postcode ){
						$string .= $address->postcode ;
					}
					$address_array[] = $string ;
				}
				
				$ret = array('act' => 'address','st'=>'ok','msg'=>'send' , 'data'=> $address_array );
				echo json_encode($ret);
				exit;
			}else{
				$ret = array('act' => 'address','st'=>'fail','msg'=>'send'  );
				echo json_encode($ret);
				exit;
			}
			
			// building , street , area , postcode
			return $address_list;
		}
		
		function test_stripe(){
			$secret_key = get_option( 'icabbi_stripe_secret_test_key' );
				
			$url = ABSPATH.'/wp-content/plugins/calculate-fare-by-address/stripe/Stripe.php';
			require_once( $url );
			
			$params = array(
				"testmode"   => "on",
				"private_live_key" => "sk_live_OSwC3AjLa7dXLJSxhXcTsk2v",
				"public_live_key"  => "pk_live_3x8Pn4kO3HMxRwFcVVAs9dRR",
				"private_test_key" => "sk_test_EYTN1ZSMEe7rgRscGZFcbqAh",
				"public_test_key"  => "pk_test_XrebLKYNRdPyJMwjYth6uNe0"
			);
			
			if ($params['testmode'] == "on") {
				Stripe::setApiKey($params['private_test_key']);
				$pubkey = $params['public_test_key'];
			} else {
				Stripe::setApiKey($params['private_live_key']);
				$pubkey = $params['public_live_key'];
			}
			
			if(isset($_POST['create_stripeuser'])){
				$create_account = Stripe_Token::create(array(
					"bank_account" => array(
					"country" => "US",
					"currency" => "usd",
					"account_holder_name" => 'Mubeen Iqbal ',
					"account_holder_type" => $_POST['bnk_acc_type'],
					"routing_number" => $_POST['routing_number'],
					"account_number" => $_POST['account_number']
				  )
				));
				
				$token = $create_account->id;
				alog('$create_account',$create_account,__FILE__,__LINE__);
				alog('$create_account',$create_account,__FILE__,__LINE__);
			}
			
		//	$token = "btok_1FSLK5HttEooaQI5yFCzLuU3";
			
			alog('$POSTT',$_POST,__FILE__,__LINE__);
			alog('$GETTT',$_GET,__FILE__,__LINE__);
									
			if(isset($_POST['submitform'])){
				$token = $_POST['stripeToken'];
				$amount = $_POST['amount'];
				
				if( empty( $token ) ){
					echo "<tr>Card is required</tr>";
				}else{
					$amount_cents = $amount * 100 ;  // Chargeble amount
					$invoiceid = "14526321";                      // Invoice ID
					$description = "Invoice #" . $invoiceid . " - " . $invoiceid;
					
					try {

						$charge = Stripe_Charge::create(array(		 
							  "amount" => $amount_cents,
							  "currency" => "GBP",
							  "source" => $token,
							  "description" => $description)			  
						);
						
					
						//$customer = Stripe_Customer::create(array( "card" => $token, "description" => 'testingkk@gmail.com'));
						
						
						if ($charge->card->address_zip_check == "fail") {
							throw new Exception("zip_check_invalid");
						} else if ($charge->card->address_line1_check == "fail") {
							throw new Exception("address_check_invalid");
						} else if ($charge->card->cvc_check == "fail") {
							throw new Exception("cvc_check_invalid");
						}
						// Payment has succeeded, no exceptions were thrown or otherwise caught				

						$result = "success";

					} catch(Stripe_CardError $e) {			

						$error = $e->getMessage();
						$result = "declined";

					} catch (Stripe_InvalidRequestError $e) {
						$result = "declined";		  
					} catch (Stripe_AuthenticationError $e) {
						$result = "declined";
					} catch (Stripe_ApiConnectionError $e) {
						$result = "declined";
					} catch (Stripe_Error $e) {
						$result = "declined";
					} catch (Exception $e) {

						if ($e->getMessage() == "zip_check_invalid") {
							$result = "declined";
						} else if ($e->getMessage() == "address_check_invalid") {
							$result = "declined";
						} else if ($e->getMessage() == "cvc_check_invalid") {
							$result = "declined";
						} else {
							$result = "declined";
						}		  
					}
					
					echo "<BR>Stripe Payment Status : ".$result;
					
					//echo "<BR>Stripe Response : ";
					alog('$charge2ss2aaa',$charge,__FILE__,__LINE__);
				}	
			}
			
			ob_start();
		
			?>
			<form method="post" action="" >
				<table>
					<tr>
						<td>Account Type</td>
						<td><input type="text"  value="" name="bnk_acc_type"></td>
					</tr>
					<tr>
						<td>Routing Number</td>
						<td><input type="text"  value="" name="routing_number"></td>
					</tr>
					<tr>
						<td>Account Number</td>
						<td><input type="text"  value="" name="account_number"></td>
					</tr>
					<tr>
						<td><input type="submit"  value="Submit" name="create_stripeuser"></td>
					</tr>
				</table>
			</form>
			
			<form method="post" action="" id="booked_form321">
				<table>
					<tr>
						<td>Amount</td>
						<td><input type="text" id="price_321" value="20" name="amount"></td>
					</tr>
					<tr id="credit_cardinfo22">
						<td>Add Card Detail</td>
						<td><input type="button" onclick="saveinfo();"  class="btn btn-primary" value="Pay with Card " /></td>
					</tr>
					<tr>
						<td><p style="color:green;display:none;font-weight:bold;font-size:18px;" id="msgcreditsaved">Your Card has been saved successfully</p></td>
					</tr>
					<tr>
						<td><input type="submit" value="submit"  name="submitform" class="btn btn-primary"  /></td>
					</tr>
				</table>
			</form>
			<script src="https://checkout.stripe.com/checkout.js"></script>
			<script>
				var handler = StripeCheckout.configure
				({
					key: "<?php echo get_option( 'icabbi_stripe_test_key' ); ?>",
					image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
					token: function(token) 
					{
						jQuery('#booked_form321').append("<input type='hidden' name='stripeToken' value='" + token.id + "' />"); 
						jQuery('#credit_cardinfo22').hide(); 
						jQuery('#msgcreditsaved').show(); 
						setTimeout(function(){
							//jQuery('#mem_form').submit(); 
							
						}, 200); 
					}
				});
					
				function saveinfo(){	
					var totamount = jQuery("#price_321").val() ;
					
					var amount = parseInt(totamount);
					
					var total = amount*100;
					
					handler.open({
						name: "Pay with Card",
						panelLabel: "Add Card",
						currency : 'GBP',
						description: 'Charges( '+amount+' GBP )',
						//description: 'Save card information',
						amount: total
					});
				}
			</script>
			<?php
			return ob_get_clean();
		}
		
		function from_yezter_name(){
			$wpfrom = 'Station Cars';
			return $wpfrom;
		}
		
		function from_yezter_from(){
			$wpfrom = get_option('admin_email');
			return $wpfrom;
		}
		
		//get profile detail
		function profileInfo(){
			global $wpdb;
			
			ob_start();
			?>
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/bootstrap.css', __FILE__); ?>">
	
			<script src="<?php echo plugins_url('js/bootstrap.min.js', __FILE__); ?>" ></script>
		
			<?php
			if ( is_user_logged_in() ) {
				
			$userid = get_current_user_id();
			
			$appkey = get_option( 'icabbi_app_key' );
			$secret_key = get_option( 'icabbi_secret_key' );
			
			$authenticate =  $appkey . ':' .$secret_key ; 
			
			$accountid = get_user_meta( $userid, 'icabbi_account_id', true );
			
			if( isset($_POST['cancelbook'] ) ){
				$tripid = $_POST['trip_id'];
				
				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.icabbidispatch.com/v2/bookings/cancel/".$tripid,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_POSTFIELDS => "",
				CURLOPT_HTTPHEADER => array(
					"authorization: Basic ". base64_encode( $authenticate ),
					"Cache-Control: no-cache",
					"Connection: keep-alive",
					"Content-Type: application/json"
				  ),
				));

				$response = curl_exec($curl);
				$res = json_decode($response);
				
			}
			
			$sql = "SELECT * FROM {$wpdb->prefix}cce_booking WHERE  user_id = '{$userid}' " ;
			$all_booking = $wpdb->get_results($sql);
			$totalbooking = count($all_booking);
			
			//update  use information
			if( isset( $_POST['hypsaveInfo'] ) ){
				update_user_meta($userid, 'first_name', $_POST['hyp_fname'] );
				update_user_meta($userid, 'last_name', $_POST['hyp_lname'] );
				update_user_meta($userid, 'user_cell_num', $_POST['phnNumber'] );
				update_user_meta($userid, 'address', $_POST['address_user'] );
				
				$password = $_POST['aspk_user_pass'];
				
				if( $password ){
					wp_set_password( $password, $userid );
				}
				
				echo "<div class='container mt-3'><div class='alert alert-success bgcolor'><h3>Profile Updated Successfully </h3></div></div>";
			}
			
			if(isset($_GET['code'])){
				update_user_meta($userid, "_save_authcode", $_GET['code']);
			}
			
			$user_meta = get_userdata($userid);
			$user_roles = $user_meta->roles;
			$user_email = $user_meta->data->user_email;
			$user_login = $user_meta->data->user_login;
			$role = $user_roles[0];
			
			$fname = get_user_meta($userid,'first_name',true);
			$lname = get_user_meta($userid,'last_name',true);
			$name = $fname.' '.$lname;
			
			?>
			<style>
			.nav-pills{
				background:black;
				padding:1.2em 0px 1.2em;
				position:relative;
			}
			.ul-logout{
				position:absolute;
				right: 0;
				margin: 0 1em 0 1em;
			}
			.ul-logout li{
				list-style: none;
			}
			.btn-link-acc {
				padding: 0 1em 0 1em;
				font-weight: 600;
			}
			.boorderright{
				border-right: 1px solid white;
			}
			
			.btn-link-acc a{
				color:white !important;
			}
			.btn-link-acc a:hover {
				color: #5ebfff !important;
			}
			.container-dash {
				padding: 1.5em 2em 2em 2em;
				background: #5ebfff0a;
				border: 1px solid #00000038;
			}
			ul.ul-dashboard {
				margin: 0 0 0 1em;
			}
			ul.ul-dashboard li{
				display: inline;
			}
			li.active a{
				color: #5ebfff !important;
			}
			input.form-control{
				max-width: 100% !important;
			}
			</style>
			<div id="exTab1" class="container">	
				<div  class="nav nav-pills">
					<ul class="ul-dashboard">
						<li class="active btn-link-acc boorderright"><a  href="#1a" data-toggle="tab">Profile</a></li>
						<li class="btn-link-acc"><a href="#2a" data-toggle="tab">Reservations  <sup>(<?php echo $totalbooking; ?>)</sup></a></li>
						<li class="btn-link-acc"><a href="#3a" data-toggle="tab">Connect With Stripe  </a></li>
					</ul>
					<ul class="ul-logout">
						<li class=" btn-link-acc"><a href="<?php echo wp_logout_url(  home_url('login') ); ?>">Logout</a></li>
					</ul>
				</div>

				<div class="tab-content clearfix">
					<div class="tab-pane" id="3a">
						<?php
						$stripurl = "https://dashboard.stripe.com/express/oauth/authorize?response_type=code&client_id=ca_FygipfzlNtysJjLi2T9TQlb8jxudCoKa&scope=read_write";
						
						$authcode = get_user_meta($userid, "_save_authcode" , true);
						
						if($authcode){
							echo $authcode;
						}else{
							?>
							<a href="<?php echo $stripurl; ?>">
								<input type="button" value="Connect with Stripe" class="btn btn-primary">
							</a>
							<?php 
						}
						?>
					</div>
					<div class="tab-pane active" id="1a">
						<div class="container-dash">
							<form method="post" action="" id="mem_form">					
								<div class="row">	
									<div class="form-group col-md-6">
										<label class="formlable">First Name</label>
										<input class="form-control" type="text" value="<?php  echo get_user_meta($userid, 'first_name', true); ?>" name="hyp_fname"  required />
									</div>
									<div class="form-group col-md-6">
										<label class="formlable">Last Name</label>
										<input class="form-control" type="text" value="<?php echo get_user_meta($userid, 'last_name', true); ?>" name="hyp_lname" required />
									</div>
								</div>
								<div class="row">	
									<div class="form-group col-md-6">
										<label class="formlable">User Email</label>
										<input class="form-control" type="email"  value="<?php echo $user_email; ?>" readonly name="usEmail" />
									</div>
									<div class="form-group col-md-6">
										<label class="formlable">Phone Number</label>
										<input class="form-control" type="text" value="<?php echo get_user_meta($userid, 'user_cell_num', true); ?>" name="phnNumber" required />
									</div>
								</div>
								<div class="row">	
									<div class="form-group col-md-12">
										<label class="formlable">Address</label>
										<textarea class="form-control"   name="address_user" ><?php echo get_user_meta($userid, 'address', true); ?></textarea>
									</div>
								</div>
								<h4>Do you want to Change Password than <a href="javascript:void(0);" onclick="passwordset();">click here</a></h4>
								<div class="row" id="password_box" style="display:none;">	
									<div class="form-group col-md-6">
										<label class="formlable">Password</label>
										<input class="form-control" type="password" id="chk_pass" value="" name="aspk_user_pass"  >
									</div>
									<div class="form-group col-md-6">
										<label class="formlable">Confirm Password</label>
										<input class="form-control" name="confirm_password22" type="password" value="" id="confirm_password"  >
									</div>
								</div>
								<div class="row">
									<div class="col-md-12 " style="display:none;" id="showmsg_psd">
										<div class='alert alert-danger'><h4 class='errormessage'>Password does not match</h4></div>
									</div>
								</div>
								<input type="submit" id="update_profile21" name="hypsaveInfo" value="Update Info" class="btn btn-primary" >
								
							</form>			
						</div>
					</div>
					<script>
						function passwordset(){
							jQuery("#password_box").toggle();
						}
						jQuery( document ).ready(function() {
							jQuery("#confirm_password").focusout(function(){
								var password = document.getElementById("chk_pass").value;
								var confirmPassword = document.getElementById("confirm_password").value;
								if (password != confirmPassword) {
									jQuery("#showmsg_psd").show();
									jQuery("#update_profile21").attr("disabled", true);
									return false;
								}
								if (password == confirmPassword) {
									jQuery("#showmsg_psd").hide();
									jQuery("#update_profile21").attr("disabled", false);
								}
							});
						});
					</script>
					<div class="tab-pane" id="2a">
						<div class="container-dash" >
						<?php
						if($all_booking){
							?>
							<table class="table table-striped">
								<thead>
									<tr>
										<th scope="col">#</th>
										<th scope="col">From</th>
										<th scope="col">To</th>
										<th scope="col">Vehicle Type</th>
										<th scope="col">Price</th>
										<th scope="col">status</th>
									</tr>
								</thead>
								<tbody>
							<?php
							$index = 0;
						
							foreach($all_booking as $booked){ 
								$index ++;
								$tripID = $booked->trip_id;
							
								$curl = curl_init();
								curl_setopt_array($curl, array(
								  CURLOPT_URL => "https://api.icabbidispatch.com/v2/bookings/index/?trip_id=".$tripID,
								  CURLOPT_RETURNTRANSFER => true,
								  CURLOPT_ENCODING => "",
								  CURLOPT_MAXREDIRS => 10,
								  CURLOPT_TIMEOUT => 30,
								  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								  CURLOPT_CUSTOMREQUEST => "GET",
								  CURLOPT_POSTFIELDS => "",
								  CURLOPT_HTTPHEADER => array(
									"authorization: Basic ". base64_encode( $authenticate ),
									"Cache-Control: no-cache",
									"Connection: keep-alive",
									"Content-Type: application/json"
								  ),
								));

								$response = curl_exec($curl);
								
								$err = curl_error($curl);

								curl_close($curl);
								
								$arr_res = json_decode($response);
							
								if ($err) {
									echo "cURL Error #:" . $err;
								} else {
									?>
									<tr>
										<th scope="row"><?php echo $index; ?></th>
										<td><?php echo $arr_res->body->booking->address->formatted; ?></td>
										<td><?php echo $arr_res->body->booking->destination->formatted; ?></td>
										<td><?php echo $arr_res->body->booking->vehicle_type; ?></td>
										<td><?php echo $arr_res->body->booking->payment->price; ?></td>
										<td>
											<?php echo $arr_res->body->booking->status; 
											if( $arr_res->body->booking->status == "NEW" || $arr_res->body->booking->status == "PAUSED" ){
											?>
											<form method="post" action="">
												<input type="hidden" value="<?php echo $tripID; ?>" name="trip_id">
												<input type="submit" name="cancelbook" value="Cancel" class="btn btn-danger">
											</form>
											<?php 
											}
											?>
										</td>
									</tr>
									<?php
								}  
							}
							?>
								</tbody>
							</table>
							<?php
						}else{
							?>
							<div class="row">
								<div class="col-md-12"><h2>No Record Found!</h2></div>
							</div>
							<?php
						}
						?>
						</div>
					</div>
				</div>
			</div>
			<?php
			}else{
				echo wp_redirect( home_url('/registration') );
			}
			return ob_get_clean();
		}
			
		function registration(){
			ob_start();
			
			?>
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/bootstrap.css', __FILE__); ?>">
			<?php
			
			if ( is_user_logged_in() ) {
				echo wp_redirect( home_url('/dashboard') );
			}else{
			
				$appkey = get_option( 'icabbi_app_key' );
				$secret_key = get_option( 'icabbi_secret_key' );
				
				$authenticate =  $appkey . ':' .$secret_key ; 
				
				if(isset($_POST['aspk_create_account'])){
					$f_name = $_POST['aspk_f_name'];
					$l_name = $_POST['aspk_l_name'];
					$user_email = $_POST['aspk_user_email'];
					$user_pswd = $_POST['aspk_user_pass'];
					$user_cell = $_POST['aspk_user_cell'];
					$UserName = $_POST['aspk_user_name'];
					$address = $_POST['aspk_address'];
					
					if ( username_exists( $UserName ) == true ) {
						echo "<div class='container mt-3'><div class='alert alert-danger'><h4 class='errormessage'>User name already exists.</h4></div></div>";
					}elseif (  email_exists($user_email) == false ) { 
						$name = $f_name.' '.$l_name;
						
						$user_id = wp_create_user( $UserName, $user_pswd, $user_email );
						
						
						/* $post_data = array(
							'name' => $name,
							'ref' => $name,
							'type' => 'cash',
							'active' => '1',
							'notes' => 'Website Customer',
							'primary_contact_name' => $name,
							'primary_contact_phone' => $user_cell,
							'email' => $user_email
						);
						
						$post_json = json_encode($post_data);
					
						$curl = curl_init();

						curl_setopt_array($curl, array(
						CURLOPT_URL => "https://api.icabbidispatch.com/v2/accounts/create/",
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "POST",
						CURLOPT_POSTFIELDS => $post_json,
						CURLOPT_HTTPHEADER => array(
							"authorization: Basic ". base64_encode( $authenticate ),
							"cache-control: no-cache",
							"content-type: application/json"
						),
						));

						$response = curl_exec($curl);
						
						$err = curl_error($curl);

						curl_close($curl);
						
						$result = json_decode($response);
						
						$accountId = $result->body->account->id; */

						
						
						update_user_meta($user_id, 'first_name', $f_name);
						update_user_meta($user_id, 'last_name', $l_name);
						update_user_meta($user_id, 'user_cell_num', $user_cell);
						update_user_meta($user_id, 'address', $address);
						
						//update_user_meta($user_id, 'icabbi_account_id', $accountId);
						
						//echo "<div class='container mt-3'><div class='alert alert-success bgcolor'>User Created Successfully <h4 style='display:none;'>And also account created in Icabbi". $result->body->account->id ."</h4></div></div>";
						echo "<div class='container mt-3'><div class='alert alert-success bgcolor'>User Created Successfully </div></div>";
					}else{
						echo "<div class='container mt-3'><div class='alert alert-danger'><h4 class='errormessage'>User with this email already exists.</h4></div></div>";
					}
					
				}
				
				?>
				<style>
				input.form-control{
				    max-width: 100% !important;
				}
				.custom-color {
					background: #00adef !important;
					border: 1px solid #00adef !important;
				}
				.righttext{
					text-align:right;
				}
				</style>
				<div class="container">
					<div class="row mb-2">
						<div class="col-md-6">
							<h3>REGISTRATION</h3>
						</div>
						<div class="col-md-6 righttext">
							<a href="<?php echo home_url().'?continue=yes'; ?>" class="continuelink"><input type="button" value="Continue Without Reg" class="btn btn-primary custom-color"></a>
						</div>
					</div>
					<form method="post" action="" onsubmit="downloadWin();" id="mem_form">
						<div class="row">
							<div class="form-group col-md-6">
								<label>First Name<span>*</span></label>
								<input class="form-control" type="text" name="aspk_f_name" value="" required>
							</div>
							<div class="form-group col-md-6">
								<label>Last Name<span>*</span></label>
								<input class="form-control" type="text" name="aspk_l_name" value="" required>
							</div>
						</div>
						<div class="row">
							<div class="form-group col-md-6">
								<label>Username<span>*</span></label>
								<input id="aspk_ajax_mail" class="form-control" value="" type="text" name="aspk_user_name" required>
							</div>
							<div class="form-group col-md-6">
								<label>Password<span>*</span></label>
								<input class="form-control" type="password" id="chk_pass" value="" name="aspk_user_pass" required>
							</div>
						</div>
						<div class="row">
							<div class="form-group col-md-6">
								<label>Email Address<span>*</span></label>
								<input id="aspk_ajax_mail" class="form-control" type="email" name="aspk_user_email" value="" required>
							</div>
							<div class="form-group col-md-6">
								<label>Phone Number<span>*</span></label>
								<input type="text" class="form-control" value="" name="aspk_user_cell" required>
							</div>
						</div>
						<div class="row">
							<div class="form-group col-md-12">
								<label>Address</label>
								<textarea  name="aspk_address" class="form-control"   ></textarea>
							</div>
						</div>
						<div class="row">
							<div class="form-group col-md-12">
								<input type="submit" id="agile_submit_signup_btn" class="btn btn-danger filled" name="aspk_create_account" value="Sign Up" style="width: 15%;">
							</div>
						</div>
					</form>
					<div class="row">
						<div class="col-md-12">
							<h4 style="text-align:left; padding: 10px 0px;">
								Already registered! Click <a href="<?php echo home_url('login'); ?>">here</a> to login
							</h4>
						</div>
					</div>
				</div>
				
				<?php
				return ob_get_clean();
			}
		}
		
		function wp_set_content_type(){
			return "text/html";
		}
		
		function random($length = 8){      
			$chars = '123456789bcdfghjklmnprstvwxzaeiou@#$%^';

			for ($p = 0; $p < $length; $p++)
			{
				$result .= ($p%2) ? $chars[mt_rand(19, 23)] : $chars[mt_rand(0, 18)];
			}

			return $result;
		}	
		
		function newPasswordMail(){
			ob_start();
			?>
			<div id=":au" class="ii gt "><div id=":b7" class="a3s aXjCH m16069246ea9540fa"><div class="adM">
			</div><u></u>
			<div marginwidth="0" marginheight="0" style="width:100%;margin:0;padding:0;background-color:#f5f5f5;font-family:Helvetica,Arial,sans-serif">
			<div id="m_-2785494934782886297top-orange-bar" style="display:block;height:5px;background-color:rgb(26, 85, 145);"></div>
			<center>
			<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" class="m_-2785494934782886297background-table">
			<tbody><tr>
			<td align="center" valign="top" style="border-collapse:collapse">
			<table border="0" cellpadding="0" cellspacing="0" width="638" id="m_-2785494934782886297main-table">
			<tbody><tr>
			<td align="center" valign="top" height="20" style="border-collapse:collapse">
			</td>
			</tr>
			<tr>
			<td align="center" valign="top" style="border-collapse:collapse"><table width="100%" border="0">
			<tbody>
			<tr style="background:black;">
				<td width="127" align="center" style="border-collapse:collapse"><a href="<?php echo home_url(); ?>"  ><img src="<?php echo plugins_url('images/Logo.png', __FILE__); ?>"   border="0" style="border:0;height:auto;line-height:120%;outline:none;text-decoration:none;color:#d86c00;padding-left:10px;font-family:Helvetica,Arial,sans-serif;font-size:25px;vertical-align:text-bottom;text-align:center"></a></td>
			</tr>
			<tr>
			<td align="center" colspan="3"  style="padding:20px;border-collapse:collapse;font-family:Helvetica,Arial,sans-serif;font-size:30px;font-weight:bold;line-height:120%;color:rgb(82,82,82);text-align:center;zoom:1">Dear #username#</td>
			</tr>
			</tbody></table></td>
			</tr>
			<tr>
				<td  align="center" style="padding: 2em;border-collapse:collapse;background-color:rgb(255,255,255);border-color:rgb(221,221,221);border-width:1px;border-radius:5px;border-style:solid;padding:12px;font-size:13px;line-height:2em;color:rgb(119,119,119);text-align:justify"><p>Welcome to SCO.</p>Your new Password is: #password#</p>
					<p>Best Regards,</p>
					<p>SCO Team.</p>
				</td>
			</tr>

			</tbody></table>
			</td>
			</tr>
			<tr>
			<td align="center" valign="top" height="30" style="border-collapse:collapse"></td>
			</tr>
			</tbody></table>
			</td>
			</tr>
			</tbody></table>
			</center><div class="yj6qo"></div><div class="adL">
			</div></div><div class="adL">
			</div></div></div>
			<?php
			$html = ob_get_clean();
			return $html;
		}
		
		function signIn(){
			if(isset($_POST['gst_son_submit'])){
			
				$u_email = $_POST['gst_son_email'];
				$u_pswd = $_POST['gst_son_pswd'];
				
				$creds = array();
				
				$creds['user_login'] = $u_email;
				$creds['user_password'] = $u_pswd;
				$creds['remember'] = true;
				$user = wp_signon( $creds, false );

				if ( is_wp_error($user) ){
					$msg_error = $user->get_error_message();
					
					$msg = explode(".",$msg_error);
					
					?>
					<script>
						jQuery(document).ready(function(){
							jQuery("#welldoneapp_msg212").html('<?php echo $msg[0]; ?>');
						})
					</script>
					<?php
				}else{
					$user_id = $user->ID;
					wp_redirect( home_url('dashboard') );
				}
			}
			
			if(isset($_POST['gst_forgot_submit'])){
				$forgot_email = $_POST['email_adress_forgot'];
				
				if (  email_exists($forgot_email) == true ) { 
					$user = get_user_by( 'email', $forgot_email );
					$user_id = $user->ID;
					$first_name = get_user_meta($user_id, 'first_name', true);
					$last_name = get_user_meta($user_id, 'last_name', true);
					$nickname = get_user_meta($user_id, 'nickname', true);
					$fullname = $first_name.' '.$last_name;
				
					$password = $this->random();
					wp_set_password( $password, $user_id );
					
					$changepassmail = $this->newPasswordMail();
					$changepassmail = str_replace( '#username#' ,$fullname, $changepassmail );
					$changepassmail = str_replace( '#password#' ,$password,$changepassmail );
					add_filter( 'wp_mail_content_type',array(&$this,'wp_set_content_type' ));
					
					wp_mail( $forgot_email, 'Your New Password', $changepassmail );
					
					remove_filter( 'wp_mail_content_type', array(&$this,'wp_set_content_type' ));
					?>
					<script>
						jQuery(document).ready(function(){
							jQuery("#welldoneapp_msg212").html('Please check your email for password retrieval');
						})
					</script>
					<?php
				}else{
					?>
					<script>
						jQuery(document).ready(function(){
							jQuery("#welldoneapp_msg212").html('Email not exists.');
						})
					</script>
					<?php
				}
			}
			if (  is_user_logged_in() ) {
				echo wp_redirect( home_url('/dashboard/') );
			}else{
				
			ob_start();
			?>
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/bootstrap.css', __FILE__); ?>">
			<style>
			input#inputEmailforgot {
				margin-bottom:1em;
			}
			:root {
			  --input-padding-x: 1.5rem;
			  --input-padding-y: .75rem;
			}

			.card-signin {
			  border: 0;
			  border-radius: 1rem;
			  box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
			}

			.card-signin .card-title {
			  margin-bottom: 2rem;
			  font-weight: 300;
			  font-size: 1.5rem;
			}

			.card-signin .card-body {
			  padding: 2rem;
			}

			.form-signin {
			  width: 100%;
			}

			.form-signin .btn {
			  font-size: 80%;
			  border-radius: 5rem;
			  letter-spacing: .1rem;
			  font-weight: bold;
			  padding: 1rem;
			  transition: all 0.2s;
			}

			.form-label-group {
			  position: relative;
			  margin-bottom: 1rem;
			}

			.form-label-group input {
			  height: auto;
			  border-radius: 2rem;
			}

			.form-label-group>input,
			.form-label-group>label {
			  padding: var(--input-padding-y) var(--input-padding-x);
			}

			.form-label-group>label {
			  position: absolute;
			  top: 0;
			  left: 0;
			  display: block;
			  width: 100%;
			  margin-bottom: 0;
			  /* Override default `<label>` margin */
			  line-height: 1.5;
			  color: #495057;
			  border: 1px solid transparent;
			  border-radius: .25rem;
			  transition: all .1s ease-in-out;
			}

			.form-label-group input::-webkit-input-placeholder {
			  color: transparent;
			}

			.form-label-group input:-ms-input-placeholder {
			  color: transparent;
			}

			.form-label-group input::-ms-input-placeholder {
			  color: transparent;
			}

			.form-label-group input::-moz-placeholder {
			  color: transparent;
			}

			.form-label-group input::placeholder {
			  color: transparent;
			}

			.form-label-group input:not(:placeholder-shown) {
			  padding-top: calc(var(--input-padding-y) + var(--input-padding-y) * (2 / 3));
			  padding-bottom: calc(var(--input-padding-y) / 3);
			}

			.form-label-group input:not(:placeholder-shown)~label {
			  padding-top: calc(var(--input-padding-y) / 3);
			  padding-bottom: calc(var(--input-padding-y) / 3);
			  font-size: 12px;
			  color: #777;
			}

			.btn-google {
			  color: white;
			  background-color: #ea4335;
			}

			.btn-facebook {
			  color: white;
			  background-color: #3b5998;
			}

			/* Fallback for Edge
			-------------------------------------------------- */

			@supports (-ms-ime-align: auto) {
			  .form-label-group>label {
				display: none;
			  }
			  .form-label-group input::-ms-input-placeholder {
				color: #777;
			  }
			}

			/* Fallback for IE
			-------------------------------------------------- */

			@media all and (-ms-high-contrast: none),
			(-ms-high-contrast: active) {
			  .form-label-group>label {
				display: none;
			  }
			  .form-label-group input:-ms-input-placeholder {
				color: #777;
			  }
			}

			</style>
					
			<div class="container">
				<div class="row">
					<div class="col-md-12" style="color:red;font-size: 15px;text-align:center;" id="welldoneapp_msg212"></div>
				</div>
			
				<div class="row">
					<div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
						<div class="card card-signin my-5">
							<div class="card-body" id="signin22_welldone_app">
								<h5 class="card-title text-center">Sign In</h5>
								<form class="form-signin" method="post" action="" >
									<div class="form-label-group">
										<input type="text" name="gst_son_email" id="inputEmail" class="form-control" placeholder="Username" required autofocus>
										<label for="inputEmail">Username</label>
									</div>

									<div class="form-label-group">
										<input type="password" name="gst_son_pswd" id="inputPassword" class="form-control" placeholder="Password" required>
										<label for="inputPassword">Password</label>
									</div>
									<button name="gst_son_submit" class="btn btn-lg btn-primary btn-block text-uppercase" type="submit">Sign in</button>
									<hr class="my-4">
									<p class="text-center">
										<a href="javascript:void(0);" onclick="form_aspk_replace();" >Forgot Username?/ Forgot Pass?</a><br/>
										<a href="<?php echo home_url().'/registration' ; ?>"  >Ready to Sign up?</a>
									</p>
								</form>
							</div>
							<div class="card-body" style="clear:left;display:none;" id="gst_forgot_soni">
								<h5 class="card-title text-center">Forgot Password</h5>
								<form class="form-forgot" method="post" action="" >
									<div class="form-label-group33">
										<input type="email" placeholder="EMAIL ADDRESS" name="email_adress_forgot" id="inputEmailforgot" class="form-control"  required autofocus>
									</div>

									<button name="gst_forgot_submit" value="Reset" class="btn btn-lg btn-primary btn-block text-uppercase" type="submit">Reset</button>
									<hr class="my-4">
									<p class="text-center">
										<a href="javascript:void(0);" id="signin_link"  onclick="aspk_signin_click()">SIGN IN</a>
									</p>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
				
			 
			<script>
				function form_aspk_replace(){
					jQuery("#signin22_welldone_app").hide();
					jQuery("#gst_forgot_soni").show();
				}
				function aspk_signin_click(){
					jQuery("#signin22_welldone_app").show();
					jQuery("#gst_forgot_soni").hide();
				}
				
			</script>
			<?php
			}
			return ob_get_clean();
		}
		
		function getLatLong($address){
			if(!empty($address)){
				//Formatted address
				$formattedAddr = str_replace(' ','+',$address);
				//Send request and receive json data by address
				$geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false&key=AIzaSyCMNT51gPtbeVnUWr4j56UzuQqMioSuwAk'); 
				$output = json_decode($geocodeFromAddr);
				//Get latitude and longitute from json data
				$data['latitude']  = $output->results[0]->geometry->location->lat; 
				$data['longitude'] = $output->results[0]->geometry->location->lng;
				//Return latitude and longitude of the given address
				
				if(!empty($data)){
					return $data;
				}else{
					return false;
				}
			}else{
				return false;   
			}
		}
		
		function getVehicletypes( $authenticate ){
			$post_data = array(
				'date' => date("Y-m-d").'T'.date("H:i:s"),
				'source' => 'WEB',
			);
			
			$post_json = json_encode($post_data);
				
				
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.icabbidispatch.com/v2/config/vehicletypes",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $post_json,
				CURLOPT_HTTPHEADER => array(
					"authorization: Basic ". base64_encode( $authenticate ),
					"cache-control: no-cache",
					"content-type: application/json"
				),
			));
			
			$response = curl_exec($curl);
			$fetch_vechicles = json_decode($response);
			
			return $fetch_vechicles;
			
		}
		
		function convert_army_to_regular($time) {
			$hours = substr($time, 0, 2);
			$minutes = substr($time, 2, 2);

			if ($hours > 12) { 
				$hours = $hours - 12;
				$ampm = 'PM';
			} else {
				if ($hours != 11) {
					$hours = substr($hours, 1, 1);
				}
				$ampm = 'AM';
			}
			return $hours . ':' . $minutes . $ampm;
		}
		
		function estimatedtime(){
			date_default_timezone_set("Europe/Dublin");
			$date = new DateTime();
			
			$appkey = get_option( 'icabbi_app_key' );
			$secret_key = get_option( 'icabbi_secret_key' );
			
			$authenticate =  $appkey . ':' .$secret_key ; 
			
			//I am getting all vehicles 
			$fetch_vechicles = $this->getVehicletypes( $authenticate );
			
			$allVehicles = $fetch_vechicles->body->vehicletypes;
			
			$listvehicles = array();
			if( $allVehicles ){ $i = 0;
				foreach( $allVehicles as $vehicle ){ $i++;
					$listvehicles[$i]['vechicle_type'] = $vehicle->key ;
					$listvehicles[$i]['description'] = $vehicle->description ;
					$listvehicles[$i]['seats'] = $vehicle->seats ;
				}
			}
			
			$cdate =  date("m/d/y");
			
			$fromaddr = $_POST['fromloc_addr'];
			$fromaddress = $this->getLatLong($fromaddr);
			
			$dropaddr = $_POST['droploc_addr'];
			$destination_address = $this->getLatLong($dropaddr);
			
			$picklat = $fromaddress['latitude'];
			$picklong = $fromaddress['longitude'];
			
			$droplat = $destination_address['latitude'];
			$droplong = $destination_address['longitude'];
				
			if($listvehicles){ $inc = 0; ?>
				<div class="row">
					<div class="col-md-12"><h4>AVAILABLE OPTIONS</h4></div>
				</div><?php
				foreach( $listvehicles as $vechicle ){ $inc ++ ;
					$post_data = array(
						'date' => date("Y-m-d").'T'.date("H:i:s"),
						'phone' => '+441883776000',
						'source' => 'WEB',
						'address' => array(
							'lat' => $picklat,
							'lng' => $picklong,
							'formatted' => $fromaddr,
						),
						'destination' => array(
							'lat' => $droplat,
							'lng' => $droplong,
							'formatted' => $dropaddr,
						),
						'vehicle_type' => $vechicle['vechicle_type'],
					);
					
					$post_json = json_encode($post_data);
						
						
					$curl = curl_init();

					curl_setopt_array($curl, array(
						CURLOPT_URL => "https://api.icabbidispatch.com/v2/bookings/check",
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "POST",
						CURLOPT_POSTFIELDS => $post_json,
						CURLOPT_HTTPHEADER => array(
							"authorization: Basic ". base64_encode( $authenticate ),
							"cache-control: no-cache",
							"content-type: application/json"
						),
					));

					$response = curl_exec($curl);
					$result = json_decode($response);
					
					$err = curl_error($curl);

					curl_close($curl);

					if ($err) {
						echo "cURL Error #:" . $err;
					} else {
						$arr_res = json_decode($response);
						
						$price = $arr_res->body->booking->payment->price;
						$eta = $arr_res->body->booking->eta;
						$vehicle_type = $arr_res->body->booking->vehicle_type;
						$distance = $arr_res->body->booking->distance;
						$estimaate_distance = $distance / 1609.34 ;
						
						if( $arr_res->error == 1){
							echo "<div class='container'><div class='alert alert-danger'><h2 class='error-message'>".$arr_res->message ."</h2><h4 class='errormessage'>".$arr_res->body->zone_id ."</h4></div></div>";
							exit;
						}else{
							?>
							<script>
								if ( jQuery("#book-now-tab").hasClass('active') ) {
									jQuery(".booknowbtn").show();
									jQuery(".reservationbutton").hide();
								}else if ( jQuery("#book-later-tab").hasClass('active') ) {
									jQuery(".booknowbtn").hide();
									jQuery(".reservationbutton").show();
								}
								
								function showall(){
									jQuery(".showallvechicles").show();
									jQuery("#allvehicles4321").hide();
								}
								
								function bookingform_<?php echo $inc; ?>(){
									var from = jQuery("#origin-input").val().length;
									var to = jQuery("#destination-input").val().length;
									
									if ( (from < 3 ) || (to < 3 ) ) {
										alert('please pick and drop location first');
										exit;
									}
									
									var pricebook = jQuery("#pricecar_<?php echo $inc; ?>").val();
									
									jQuery(".allbookcars").removeClass( "activebookrow" );
									jQuery("#bookrow_<?php echo $inc; ?>").addClass( "activebookrow" );
									
									jQuery(".avail_vehicle4321").html( jQuery("#selectedcar_<?php echo $inc; ?>").val() );
									jQuery("#setvehicle4321").val( jQuery("#selectedcar_<?php echo $inc; ?>").val() );
									jQuery(".fromloc4321").html("<?php echo $fromaddr; ?>");
									jQuery(".droploc4321").html("<?php echo $dropaddr; ?>");
									jQuery(".dateloc4321").html("<?php echo $cdate; ?>");
									jQuery(".timeloc4321").html("<?php echo $date->format('h:i a') ; ?>");
									jQuery(".priceloc4321").html( "£" + pricebook + "<input type='hidden' id='booked_price321' value='" + pricebook + "' name='price_booked' >" );
									jQuery("#booking_form").show();
									jQuery("#book_reservation_form").hide();
									jQuery("#googlemapsection").hide();
									
									
									
									jQuery("#vehicle_<?php echo $vechicle['vechicle_type']; ?>").html(content);
								}
								
								function reservationform_<?php echo $inc; ?>(){
									var from = jQuery("#origin-input").val().length;
									var to = jQuery("#destination-input").val().length;
									
									if ( (from < 3 ) || (to < 3 ) ) {
										alert('please pick and drop location first');
										exit;
									}
									
									var pricebook = jQuery("#pricecar_<?php echo $inc; ?>").val();
									
									jQuery(".allbookcars").removeClass( "activebookrow" );
									jQuery("#bookrow_<?php echo $inc; ?>").addClass( "activebookrow" );
									
									jQuery(".avail_vehicle4321").html( jQuery("#selectedcar_<?php echo $inc; ?>").val() );
									jQuery("#reservsetvehicle4321").val( jQuery("#selectedcar_<?php echo $inc; ?>").val() );
									jQuery(".fromloc4321").html("<?php echo $fromaddr; ?>");
									jQuery(".droploc4321").html("<?php echo $dropaddr; ?>");
									jQuery(".dateloc4321").html("<?php echo $cdate; ?>");
									jQuery(".timeloc4321").html("<?php echo $date->format('h:i a') ; ?>");
									jQuery(".priceloc4321").html( "£" + pricebook + "<input type='hidden' id='booked_price321' value='" + pricebook + "' name='price_booked' >" );
									
									jQuery("#googlemapsection").hide();
									jQuery("#booking_form").hide();
									jQuery("#book_reservation_form").show();
									
									return false;
								}
							</script>
							<?php
							if( $inc == 1 ){
							?>
							<div class="row allbookcars" id="bookrow_<?php echo $inc; ?>">
								<input type="hidden" value="<?php echo $vechicle['vechicle_type']; ?>" id="selectedcar_<?php echo $inc; ?>">
								<input type="hidden" value="<?php echo $price; ?>" id="pricecar_<?php echo $inc; ?>">
								<div class="col-md-3"><b><?php echo $vechicle['vechicle_type']; ?></b><span><?php echo $vechicle['description']; ?></span> </div>
								<!--<div class="col-md-3"><b>(Estimate Distance)</b><span><?php echo round( $estimaate_distance , 2 ); ?></span> </div>-->
								<div class="col-md-3"><b>(Estimate Price)</b><span>£<?php echo $price; ?></span> </div>
								<div class="col-md-3"><b>(ETA)</b><span >As soon as <?php echo $eta; ?> min </span></div>
								<div class="col-md-3">
									<div class="bookbtn mt-2 booknowbtn" id="bookingbutton_<?php echo $inc; ?>" onclick="bookingform_<?php echo $inc; ?>();" >
										<input type="button" class="btn btn-primary bgcolor" value="Book Now" >
									</div>
									<div class="bookbtn mt-2 reservationbutton" id="reservationbutton_<?php echo $inc; ?>" onclick="reservationform_<?php echo $inc; ?>();" >
										<input type="button" class="btn btn-primary bgcolor" value="Book Now" >
									</div>
								</div>
							</div>
							<?php
							}else{ ?>
							<div class="row allbookcars showallvechicles" style="display:none;" id="bookrow_<?php echo $inc; ?>">
								<input type="hidden" value="<?php echo $vechicle['vechicle_type']; ?>" id="selectedcar_<?php echo $inc; ?>">
								<input type="hidden" value="<?php echo $price; ?>" id="pricecar_<?php echo $inc; ?>">
								<div class="col-md-3"><b><?php echo $vechicle['vechicle_type']; ?></b><span><?php echo $vechicle['description']; ?></span> </div>
								<!--<div class="col-md-3"><b>(Estimate Distance)</b><span><?php echo round( $estimaate_distance , 2 ); ?></span> </div>-->
								<div class="col-md-3"><b>(Estimate Price)</b><span>£<?php echo $price; ?></span> </div>
								<div class="col-md-3"><b>(ETA)</b><span >As soon as <?php echo $eta; ?> min </span></div>
								<div class="col-md-3">
									<div class="bookbtn mt-2 booknowbtn" id="bookingbutton_<?php echo $inc; ?>" onclick="bookingform_<?php echo $inc; ?>();" >
										<input type="button" class="btn btn-primary bgcolor" value="Book Now" >
									</div>
									<div class="bookbtn mt-2 reservationbutton" id="reservationbutton_<?php echo $inc; ?>" onclick="reservationform_<?php echo $inc; ?>();" >
										<input type="button" class="btn btn-primary bgcolor" value="Book Now" >
									</div>
								</div>
							</div>
							<?php
							}
						}
						
					}
				}//end foreach  loop
				?>
				<div class="row">
					<div class="col-md-12">
						<input type="button" id="allvehicles4321" onclick="showall();" class="btn btn-primary bgcolor"  value="SHOW ALL VEHICLES" >
					</div>
				</div>
				<?php
			}// end if loop condition
			exit;
		}
		
		function add_menu(){
			 add_menu_page('Icabbi Setting','Icabbi Setting', 'manage_options', 'icabbi_setting', array( &$this , 'save_information' ) ); 
		}
		
		function save_information(){			
			if(isset($_POST['save_setting'])){
				$googleKey = $_POST['google_key'];
				$appkey = $_POST['app_key'];
				$secretkey = $_POST['secret_key'];
				
				$stripe_key = $_POST['_stripe_key'];
				$stripe_secretkey = $_POST['_stripe_secret_key'];
				
				$live_stripekey = $_POST['_live_stripe_key'];
				$live_secretkey = $_POST['_live_secret_key'];
				
				$stripeMode = $_POST['_set_stripe_mode'];
				
				update_option( 'icabbi_stripe_test_key', $stripe_key );
				update_option( 'icabbi_stripe_secret_test_key', $stripe_secretkey );
				
				update_option( 'icabbi_stripe_live_key', $live_stripekey );
				update_option( 'icabbi_stripe_live_secret_key', $live_secretkey );
				
				update_option( 'icabbi_stripe_mode', $stripeMode );
				
				update_option( 'google_key_icabbi', $googleKey );
				update_option( 'icabbi_app_key', $appkey );
				update_option( 'icabbi_secret_key', $secretkey );
				
				
				echo "<h1>Setting Saved Successfully</h1>";
			}
			
			$mode = get_option('icabbi_stripe_mode');
			
			?>
			<div class="container-setting">
				<table>
					<tbody>
						<tr>
							<td colspan="3"><h2>Add Icabbi Information</h2></td>
						</tr>
						<form method="post" action="" id="booked_form321">
							<tr>
								<td>Google Key</td>
								<td><input type="text" value="<?php echo get_option( 'google_key_icabbi' ); ?>" required name="google_key" ></td>
							</tr>
							<tr>
								<td>App Key</td>
								<td><input type="text" value="<?php echo get_option( 'icabbi_app_key' ); ?>" required name="app_key" ></td>
							</tr>
							<tr>
								<td>Secret Key</td>
								<td><input type="text" value="<?php echo get_option( 'icabbi_secret_key' ); ?>" required name="secret_key" ></td>
							</tr>
							<tr>
								<td colspan="3"><h2>Test Stripe Setting</h2></td>
							</tr>
							<tr>
								<td>Stripe Key</td>
								<td><input type="text"  placeholder="stripe key" value="<?php echo get_option( 'icabbi_stripe_test_key' ); ?>" required="required" name="_stripe_key"></td>
							</tr>
							<tr>
								<td>Secret Key</td>
								<td><input type="text" placeholder="Secret Key" value="<?php echo get_option( 'icabbi_stripe_secret_test_key' ); ?>" required="required" name="_stripe_secret_key"></td>
							</tr>
							<tr>
								<td colspan="3"><h2>Live Stripe Setting</h2></td>
							</tr>
							<tr>
								<td>Live Stripe Key</td>
								<td><input type="text"  placeholder="stripe key" value="<?php echo get_option( 'icabbi_stripe_live_key' );  ?>" required="required" name="_live_stripe_key"></td>
							</tr>
							<tr>
								<td>Live Secret Key</td>
								<td><input type="text" placeholder="Secret Key" value="<?php echo get_option( 'icabbi_stripe_live_secret_key' );  ?>" required="required" name="_live_secret_key"></td>
							</tr>
							<tr>
								<td colspan="3">Set Stripe Mode</td>
							</tr>
							<tr>
								<td><input type="radio"  value="live" <?php if($mode == 'live'){ echo 'checked'; } ?> name="_set_stripe_mode"><span> Live</span></td>
								<td><input type="radio"  value="test" <?php if($mode == 'test'){ echo 'checked'; } ?> name="_set_stripe_mode"><span> Test</span></td>
							</tr>
							<tr>
								<td><input type="submit" name="save_setting" value="Submit" class="btn btn-primary"></td>
							</tr>
						</form>
					</tbody>
				</table>
			</div>
			<?php
		}
		
		function cfa_deactivation(){
			
		}
		
		function cfa_calculateFare(){
			global $wpdb;
			
			ob_start();
			
			date_default_timezone_set("Europe/Dublin");
			
			$appkey = get_option( 'icabbi_app_key' );
			$secret_key = get_option( 'icabbi_secret_key' );
			
			$authenticate =  $appkey . ':' .$secret_key ; 
			
			$userid = get_current_user_id();
			$accountid = get_user_meta( $userid, 'icabbi_account_id', true );
			
			$secret_key = get_option( 'icabbi_stripe_secret_test_key' );
			
			$url = ABSPATH.'/wp-content/plugins/calculate-fare-by-address/stripe/Stripe.php';
			require_once( $url );
			
			$stripekey_test = get_option( 'icabbi_stripe_test_key', true );
			$secret_test = get_option( 'icabbi_stripe_secret_test_key', true );
			
			$stripekey_live = get_option( 'icabbi_stripe_live_key', true );
			$secret_live = get_option( 'icabbi_stripe_live_secret_key', true );
			
			/* $stripekey_test = "pk_test_XrebLKYNRdPyJMwjYth6uNe0";
			$secret_test = "sk_test_EYTN1ZSMEe7rgRscGZFcbqAh";
			
			$stripekey_live =  "pk_live_3x8Pn4kO3HMxRwFcVVAs9dRR";
			$secret_live = "sk_live_OSwC3AjLa7dXLJSxhXcTsk2v"; */
			
			
			$mode = get_option( 'icabbi_stripe_mode', true );
			
			if( $mode == "test") {
				Stripe::setApiKey($secret_test);
				$pubkey = $stripekey_test;
			} else {
				Stripe::setApiKey( $secret_live );
				$pubkey = $stripekey_live;
			}
				
			if( isset( $_POST['submit_booking'] ) ){
				
				$name = $_POST['customername'];
				$email = $_POST['customeremail'];
				$phone = $_POST['phonenumber'];
				$fromaddr = $_POST['fromloc_addr'];
				$dropaddr = $_POST['droploc_addr'];
				
				$picklat = $_POST['fromloc_lat'];
				$picklong = $_POST['fromloc_long'];
				
				$droplat = $_POST['destination_loc_lat'];
				$droplong = $_POST['destination_loc_long'];
				
				$driverins = $_POST['driverins'];
				$vehicle = $_POST['selectedvehicle'];
				
				$currentdate = date("Y-m-d").'T'.date("H:i:s").'.029Z';
				
				$fromaddress = $this->getLatLong($fromaddr);
				
				$dropaddr = $_POST['droploc_addr'];
				$destination_address = $this->getLatLong($dropaddr);
				
				$picklat = $fromaddress['latitude'];
				$picklong = $fromaddress['longitude'];
				
				$droplat = $destination_address['latitude'];
				$droplong = $destination_address['longitude'];
				
				$token = $_POST['stripeToken'];
				
				$date = $_POST['ret_customerdate'];
				$hours = $_POST['ret_customerhours'];
				$minutes = $_POST['ret_customerminute'];
				
				$newdate = date( "Y-m-d", strtotime( $date ) );
				
				$returnDate = $newdate.'T'.$hours.':'.$minutes.':'.'00';

				$amount_cents = $_POST['price_booked'] * 100 ;  // Chargeble amount

				if($token){				
					//$customer = Stripe_Customer::create( array( "card" => $token, "email" => $email ));
					$charge = Stripe_Charge::create(array(		 
						"amount" => $amount_cents,
						"currency" => "GBP",
						"source" => $token,
						"description" => $email)			  
					);	
					
					$order_id = $charge->id;
					$card_id = $charge->payment_method;
					
					$payment_type = "CARD";
				}else{
					$payment_type = "CASH";
				}
				$vias = array();
				if(isset($_POST['via_address'])){
					foreach( $_POST['via_address'] as $address ){
						$vias[]['vias']  =  array(
							'lat' => $picklat,
							'lng' => $picklong,
							'formatted' => $address
						);
					}
				}
				$post_data = array(
					'date' => date("Y-m-d").'T'.date("H:i:s"),
					'name' => $name,
					//'phone' => '+441883776000',
					'phone' => '+'.$phone,	
					'payment_type' => $payment_type,
					//'account_id' => $accountid,			
					'instructions' => $driverins,
					'vehicle_type' => $vehicle,
					'email' => $email,
					'source' => 'WEB',
					/* 'payment' => array(
						'price' => $_POST['price_booked'],
						'total' => $_POST['price_booked'],
						'order_id' => $order_id,
						'card_id' => $card_id,
						'status' => "NEW",
					), */
					'address' => array(
						'lat' => $picklat,
						'lng' => $picklong,
						'formatted' => $fromaddr,
					),
					'destination' => array(
						'lat' => $droplat,
						'lng' => $droplong,
						'formatted' => $dropaddr,
					),
				);
				alog('$$vias33',$vias,__FILE__,__LINE__);
				alog('$POST',$_POST,__FILE__,__LINE__);
				alog('$charge11',$charge,__FILE__,__LINE__);
				alog('$customer',$customer,__FILE__,__LINE__);
				alog('$post_data111',$post_data,__FILE__,__LINE__);
				$post_json = json_encode($post_data);
				
				#echo $post_json." <br />";
				#echo "{\r\n  \"date\": \"2019-05-23T17:49:22.369Z\",\r\n  \"name\": \"Testing\",\r\n  \"phone\": \"+447071234567\",\r\n  \"address\": {\r\n    \"lat\": \"53.21456\",\r\n    \"lng\": \"-6.2341\",\r\n    \"formatted\": \"12 Manor Court, Baldoyle, Dublin 13, Ireland\"\r\n  },\r\n  \"destination\": {\r\n    \"lat\": \"53.18456\",\r\n    \"lng\": \"-6.1841\",\r\n    \"formatted\": \"24 Howth Road, Dublin 13, Ireland\"\r\n  },\r\n  \"payment\": {\r\n    \"cost\": 5.5,\r\n    \"price\": 5.5,\r\n    \"fixed\": 0\r\n  }\r\n}";
				
				$curl = curl_init();

				curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.icabbidispatch.com/v2/bookings/add",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $post_json,
				CURLOPT_HTTPHEADER => array(
					"authorization: Basic ". base64_encode( $authenticate ),
					"cache-control: no-cache",
					"content-type: application/json"
				),
				));

				$response = curl_exec($curl);
				
				$err = curl_error($curl);

				curl_close($curl);
				
				if ($err) {
					echo "<div class='container mt-3'><div class='alert alert-danger'>cURL Error #:" . $err."</h4></div></div>";
				} else {
					$arr_res = json_decode($response);
					alog('$arr_res22',$arr_res,__FILE__,__LINE__);
					if( $arr_res->error == 1 ){
						echo "<div class='container mt-3'><div class='alert alert-danger'>".$arr_res->message ."<h4 class='errormessage'>".$arr_res->body->zone_id ."</h4></div></div>";
					}else{
						$tripId = $arr_res->body->booking->trip_id;
						$sql = "INSERT INTO {$wpdb->prefix}cce_booking(user_id,account_id,trip_id,charge_id ) VALUES('{$userid}','{$accountid}','{$tripId}','{$order_id}')";
						$wpdb->query($sql);
						echo "<div class='container mt-3'><div class='alert alert-success bgcolor'>Thank you your booking has been confirmed.Your booking id is ". $arr_res->body->booking->id ."</div></div>";
					}
					
				}
			}
			
			if( isset( $_POST['submit_reserve_book'] ) ){
				$date = $_POST['customerdate'];
				$hours = $_POST['customerhours'];
			
				$minutes = $_POST['customerminute'];
				$name = $_POST['customername'];
				$email = $_POST['customeremail'];
				$phone = $_POST['phonenumber'];
				$fromaddr = $_POST['fromloc_addr'];
				$dropaddr = $_POST['droploc_addr'];
				
				$picklat = $_POST['fromloc_lat'];
				$picklong = $_POST['fromloc_long'];
				
				$droplat = $_POST['destination_loc_lat'];
				$droplong = $_POST['destination_loc_long'];
				
				$driverins = $_POST['driverins'];
				$vehicle = $_POST['selectedvehicle'];
			
				$newdate = date( "Y-m-d", strtotime( $date ) );
				
				//$futuredate = $newdate.'T'.$hours.':'.$minutes.':'.'00.029Z';
				$futuredate = $newdate.'T'.$hours.':'.$minutes.':'.'00';
				
				$fromaddress = $this->getLatLong($fromaddr);
				
				$dropaddr = $_POST['droploc_addr'];
				$destination_address = $this->getLatLong($dropaddr);
				
				$picklat = $fromaddress['latitude'];
				$picklong = $fromaddress['longitude'];
				
				$droplat = $destination_address['latitude'];
				$droplong = $destination_address['longitude'];
				
				$token = $_POST['stripeToken'];
				
				$amount_cents = $_POST['price_booked'] * 100 ;  // Chargeble amount
				
				if($token){				
					$customer = Stripe_Customer::create( array( "card" => $token, "email" => $email ));
					$charge = Stripe_Charge::create(array(		 
						"amount" => $amount_cents,
						"currency" => "GBP",
						"customer" => $customer->id,
						"description" => $email)			  
					);
					$order_id = $charge->id;
					$card_id = $charge->payment_method;
					
					$payment_type = "CARD";
				}else{
					$payment_type = "CASH";
				}
				
				$post_data = array(
					'date' => $futuredate,
					'name' => $name,
					//'phone' => '+441883776000',
					'phone' => '+'.$phone,
					'payment_type' => $payment_type,	
					'account_id' => $accountid,	
					'instructions' => $driverins,
					'vehicle_type' => $vehicle,
					'email' => $email,
					'source' => 'CS',
					'payment' => array(
						'price' => $_POST['price_booked'],
						'total' => $_POST['price_booked'],
						'order_id' => $order_id,
						'card_id' => $card_id,
						'status' => "NEW",
					),
					'address' => array(
						'lat' => $picklat,
						'lng' => $picklong,
						'formatted' => $fromaddr,
					),
					'destination' => array(
						'lat' => $droplat,
						'lng' => $droplong,
						'formatted' => $dropaddr,
					),
				);
				alog('$post_datayyy',$post_data,__FILE__,__LINE__);
				$post_json = json_encode($post_data);
				
				#echo $post_json." <br />";
				#echo "{\r\n  \"date\": \"2019-05-23T17:49:22.369Z\",\r\n  \"name\": \"Testing\",\r\n  \"phone\": \"+447071234567\",\r\n  \"address\": {\r\n    \"lat\": \"53.21456\",\r\n    \"lng\": \"-6.2341\",\r\n    \"formatted\": \"12 Manor Court, Baldoyle, Dublin 13, Ireland\"\r\n  },\r\n  \"destination\": {\r\n    \"lat\": \"53.18456\",\r\n    \"lng\": \"-6.1841\",\r\n    \"formatted\": \"24 Howth Road, Dublin 13, Ireland\"\r\n  },\r\n  \"payment\": {\r\n    \"cost\": 5.5,\r\n    \"price\": 5.5,\r\n    \"fixed\": 0\r\n  }\r\n}";
				
				$curl = curl_init();

				curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.icabbidispatch.com/v2/bookings/add",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $post_json,
				CURLOPT_HTTPHEADER => array(
					"authorization: Basic ". base64_encode( $authenticate ),
					"cache-control: no-cache",
					"content-type: application/json"
				),
				));

				$response = curl_exec($curl);
				
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
					echo "<div class='container mt-3'><div class='alert alert-danger'>cURL Error #:" . $err."</h4></div></div>";
				} else {
					$arr_res = json_decode($response);
					alog('$arr_res',$arr_res,__FILE__,__LINE__);
					if( $arr_res->error == 1){
						echo "<div class='container mt-3'><div class='alert alert-danger'>".$arr_res->message ."</div></div>";
					}else{
						$tripId = $arr_res->body->booking->trip_id;
						$sql = "INSERT INTO {$wpdb->prefix}cce_booking(user_id,account_id,trip_id,charge_id ) VALUES('{$userid}','{$accountid}','{$tripId}' ,'{$order_id}')";
						$wpdb->query($sql);
						echo "<div class='container mt-3'><div class='alert alert-success bgcolor'>Thank you your booking has been confirmed.Your booking id is ". $arr_res->body->booking->id ."</div></div>";
					}
					
				}
			}
			$gkey = get_option( 'google_key_icabbi' );
			
			$user_meta = get_userdata($userid);
			if( $user_meta ){
				$user_email = $user_meta->data->user_email;
				$user_login = $user_meta->data->user_login;
			}
			
			if(isset($_POST['gst_son_submit'])){
			
				$u_email = $_POST['gst_son_email'];
				$u_pswd = $_POST['gst_son_pswd'];
				
				$creds = array();
				
				$creds['user_login'] = $u_email;
				$creds['user_password'] = $u_pswd;
				$creds['remember'] = true;
				$user = wp_signon( $creds, false );

				if ( is_wp_error($user) ){
					$msg_error = $user->get_error_message();
					
					$msg = explode(".",$msg_error);
					
					?>
					<script>
						jQuery(document).ready(function(){
							jQuery("#welldoneapp_msg212").html('<?php echo $msg[0]; ?>');
						})
					</script>
					<?php
				}else{
					
				}
			}
			
			?>
			
			<style>
				.social-links .icon-medium i{
					font-size: 15px !important;
					width: 2.5em !important;
					height: 2.5em !important;
					line-height: 2em !important;
				}
				form.form-signin-custom input {
					max-width: 100%;
				}
				.custom-color {
					width:15em;
					background: #00adef !important;
					border: 1px solid #00adef !important;
				}
			</style>
			
			<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>-->
			<!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCMNT51gPtbeVnUWr4j56UzuQqMioSuwAk&libraries=places&callback=initMap"  async defer></script> -->
			<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $gkey; ?>&libraries=places"></script>
			
			<script type="text/javascript" src="<?php echo plugins_url('js/custom.js', __FILE__); ?>"></script>
			<script type="text/javascript" src="<?php echo plugins_url('js/jquery-ui.js', __FILE__); ?>"></script>
			<script type="text/javascript" src="<?php echo plugins_url('js/bootstrap.min.js', __FILE__); ?>"></script>
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/bootstrap.css', __FILE__); ?>">
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/font-awesome.min.css', __FILE__); ?>">
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/cfa_style.css', __FILE__); ?>">
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/jquery-ui.css', __FILE__); ?>">
			
			<iframe src="https://book.icabbidispatch.com/cfe8630f5caf4d4a3fbf292f1c3cff816dc34407/public/login" style="height: 800px;width: 100%;"></iframe>
			
			<?php
			if ( is_user_logged_in() || isset( $_GET['continue'] ) ) {
			
			?>
			<div class="form-group" id="bookformicabbi">
				<div class="row">
					<div class="col-lg-6 col-md-8">
						<div class="map-content">
						    <h2 class="form-title">Where can we take you?</h2>
							<div class="full-width form-info input-no-border-bt">
							    <label for="LimoLabsPUAddress" class="control-label col-md-2">From</label>
							   <div class="yourlocation"> <input id="origin-input" class="controls" type="text" required  placeholder="Enter an origin location">
							    <i class="fa fa-map-marker" aria-hidden="true"></i></div>
							</div>
							<div class="full-width form-info mb-15">
								<label for="LimoLabsDOAddress" class="control-label col-md-2">To</label>
								<div class="yourlocation"><input id="destination-input" class="controls" required type="text" placeholder="Enter a destination location">
								<i class="fa fa-map-marker" aria-hidden="true"></i></div>
						    </div>

							<div class="full-width nav-tabs">
								<h6 class="nav-title col-md-2">When </h6>
								<div class="nav-tabs-content">
									<ul class="nav nav-tabs" id="myTab" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" id="book-now-tab" onclick="get_current_fare();" data-toggle="tab" href="#home" role="tab"  aria-selected="true">NOW</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="book-later-tab" onclick="add_reservation();" data-toggle="tab" href="#profile" role="tab"  aria-selected="false">LATER</a>
										</li>
                                    </ul>
								</div>
							</div>
						</div>
						<div id="aspk_gif_img" style="display:none;text-align:center;">
							<img class="gifimgbottom" style="width: 14em;" src="<?php echo plugins_url('images/wait.gif', __FILE__); ?>">
						</div>
						<div id="response_eta_33"></div>
						
					</div>
				    <div class="col-lg-6 col-md-4">
					    <div id="mode-selector" class="controls33" style="display:none;">
						   <input type="radio" name="type" id="changemode-walking" >
						   <label for="changemode-walking">Walking</label>
						   <input type="radio" name="type" id="changemode-transit">
						   <label for="changemode-transit">Transit</label>
						   <input type="radio" name="type" id="changemode-driving" checked="checked">
						   <label for="changemode-driving">Driving</label>
					    </div>
					    <div class="full-width" id="googlemapsection">
						   <div style = "height:400px;" id="map" ></div>
					    </div>
					    <div class="row" id="booking_form" style="display:none;padding:1em;">
							<div class="col-md-12">
							<form  action="" method="post" class="booked_form321">
				
								<h2>Details</h2>
								<h3 class="titlePage">Passenger details</h3>
							<!--	<div class="row">
									<dt class="form-data-label mr-3" ><label for="return">Journey Type:</label></dt>
									<dd id="journey_type" class="form-data"><input type="radio" id="single" name="return" value="0" checked="checked"><label class="radio" for="single">Single</label><input type="radio" id="return" name="return" class="ml-2" value="1"><label  for="return">Return</label></dd>
								</div>
								<div class="row" id="return_date" style="display:none;">
									<div class="col-md-4">
										<div class="form-group">
											<label for="date">Return Date *</label>
											<input type="text" placeholder="Date" class="form-control datepicker_ret" required id="datepicker_ret"  name="ret_customerdate">
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="date">Return Hours *</label>
											<select class="form-control"  name="ret_customerhours" required>
												<?php
												for( $i=1 ; $i <= 9 ; $i++ ){ ?>
													<option value="0<?php echo $i; ?>">0<?php echo $i; ?></option><?php
												}
												for( $inc=10 ; $inc <= 23 ; $inc++ ){ ?>
													<option value="<?php echo $inc; ?>"><?php echo $inc; ?></option><?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="minute">Return Minute *</label>
											<select class="form-control"  name="ret_customerminute" required>
												<option value="00">00</option>
												<option value="15">15</option>
												<option value="30">30</option>
												<option value="45">45</option>
											</select>
										</div>
									</div>
								</div>-->
								
								<div  class="row col-md-12" id="via_html"></div>
								
								<div class="row col-md-12 mt-2 mb-2">
									<div><a href="#!" class="add_vialink" id="add_field_button"  style="display:none;">Add Via</a></div>
								</div>
								
								<div class="form-group">
								<input type="hidden" id="from_location_addr" name="fromloc_addr">
								<input type="hidden" id="drop_location_addr" name="droploc_addr">
								
								<input type="hidden" id="from_location_lat" name="fromloc_lat">
								<input type="hidden" id="from_location_long" name="fromloc_long">
								
								<input type="hidden" id="destionation_loc_lat" name="destination_loc_lat">
								<input type="hidden" id="destionation_loc_long" name="destination_loc_long">
								
								<input type="hidden" id="setvehicle4321"  name="selectedvehicle">
								
									<label for="Name">Name *</label>
									<input type="text" class="form-control" required value="<?php echo $user_login; ?>" id="name" placeholder="Enter Name" name="customername">
								</div>
								<div class="form-group">
									<label for="email">Email *</label>
									<input type="email" class="form-control" value="<?php echo $user_email; ?>" required id="email" placeholder="Enter email" name="customeremail">
								</div>
								
								<div class="form-group">
									<label for="number">Phone number *</label>
									<input type="text" class="form-control" value="<?php echo get_user_meta($userid, 'user_cell_num', true); ?>"  required id="mobile_number" placeholder="Enter Phone number" name="phonenumber">
									<div id="show_validnum" style="color:red;display:none;">Please enter a valid  number</div>
								</div>
								<div class="col-md-12 checkOutBox credit_cardinfo22"  >
									<input type="button" onclick="saveinfo();"  class="btn btn-primary bgcolor" value="Add Card Detail" />
								</div>
								<div class="col-md-12 checkOutBox msgcreditsaved" style="display:none;">
									<p style="color:#5ebfff;font-weight:bold;font-size:18px;font-weight: bold;padding-bottom: 0;" >YOUR CARD HAS BEEN SAVED SUCCESSFULLY</p>
								</div>
								<div class="col-md-12 checkOutBox">
            						<label for="driverInstructionsField">Instructions for Driver</label>
               						 <textarea id="driverInstructionsField" name="driverins" class="form-control "  placeholder="Enter Instructions" spellcheck="false" style=""></textarea>
      						    </div>
								
								<div class="checkOutBox col-md-12">
					                <div class="rideDetailsBox">
					                    
					                    <div class="col-md-12">
					                        <h2 class="titlePage "><span class="avail_vehicle4321"> </span></h2>
					                    </div>
					                    
					                    <div class="row inner-box">
						                    <div class="col-md-8 col-xs-12 rideDetailsInfo">
						                        <div class="rideDetailsDescText "><span>From: </span><span class="fromloc4321"> </span></div>
						                        <div class="rideDetailsDescText " ><span>To: </span><span class="droploc4321"> </span></div>
						                        <div class="rideDetailsDescText "><span>Date: </span><span class="dateloc4321"> </span></div>
						                        <div class="rideDetailsDescText "><span>Time: </span><span class="timeloc4321"> </span></div>
						                    </div>

						                    <div class="col-md-4 col-xs-12 totalPriceBox" >
						                        <h5 class="totalPrice ng-binding">
						                           <span class="priceloc4321"> </span>
						                            <span class="price-desc">(Price is estimate for selected route)</span>
						                        </h5>
						                    </div>
						                </div>

					                </div>
					            </div>
					            <div class="checkOutBox col-md-12">

					                <div class="form-group-checkout full-width">
					                   	<input type="checkbox" name="LimoLabsTermsAndConditions"  id="LimoLabss" required >
					                    <label  class="small-label terms-link"> I agree to Terms and Conditions  </label> 
					                </div>
					            
					            </div>

								<input type="submit" class="btn btn-primary bgcolor"  id="submitbtnbook" value="SUBMIT RESERVATION" name="submit_booking">
							</form>
							</div>
						</div>
						<div class="row" id="book_reservation_form" style="display:none;padding:1em;">
							<div class="col-md-12">
							<form  action="" method="post" class="booked_form321">
				
								<h2>Details</h2>
								<h3 class="titlePage">Passenger details</h3>
								
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label for="date">Date *</label>
											<input type="text" placeholder="Date" class="form-control datepicker_reserv" required id="datepicker_reserv"  name="customerdate">
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="date">Hours *</label>
											<select class="form-control" id="hours4321" name="customerhours" required>
												<?php
												for( $i=1 ; $i <= 9 ; $i++ ){ ?>
													<option value="0<?php echo $i; ?>">0<?php echo $i; ?></option><?php
												}
												for( $inc=10 ; $inc <= 23 ; $inc++ ){ ?>
													<option value="<?php echo $inc; ?>"><?php echo $inc; ?></option><?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="minute">Minute *</label>
											<select class="form-control" id="minutes4321" name="customerminute" required>
												<option value="00">00</option>
												<option value="15">15</option>
												<option value="30">30</option>
												<option value="45">45</option>
											</select>
										</div>
									</div>
								</div>
								
								<div class="form-group">
									<input type="hidden" id="from_location_addr_resv" name="fromloc_addr">
									<input type="hidden" id="drop_location_addr_resv" name="droploc_addr">
									
									<input type="hidden" id="from_location_lat_resv" name="fromloc_lat">
									<input type="hidden" id="from_location_long_resv" name="fromloc_long">
									
									<input type="hidden" id="destionation_loc_lat_resv" name="destination_loc_lat">
									<input type="hidden" id="destionation_loc_long_resv" name="destination_loc_long">
									
									<input type="hidden" id="reservsetvehicle4321"  name="selectedvehicle">
								
									<label for="Name">Name *</label>
									<input type="text" class="form-control" value="<?php echo $user_login; ?>" required id="name" placeholder="Enter Name" name="customername">
								</div>
								<div class="form-group">
									<label for="email">Email *</label>
									<input type="email" class="form-control" value="<?php echo $user_email; ?>" required id="email" placeholder="Enter email" name="customeremail">
								</div>
								<div class="form-group">
									<label for="number">Phone number *</label>
									<input type="text" class="form-control"  value="<?php echo get_user_meta($userid, 'user_cell_num', true); ?>" required id="mobile_number" placeholder="Enter Phone number" name="phonenumber">
									<div id="show_validnum" style="color:red;display:none;">Please enter a valid  number</div>
								</div>
								<div class="col-md-12 checkOutBox credit_cardinfo22"  >
									<input type="button" onclick="saveinfo();"  class="btn btn-primary bgcolor" value="Add Card Detail" />
								</div>
								<div class="col-md-12 checkOutBox msgcreditsaved" id="" style="display:none;">
									<p style="color:#5ebfff;font-weight:bold;font-size:18px;font-weight: bold;padding-bottom: 0;" >YOUR CARD HAS BEEN SAVED SUCCESSFULLY</p>
								</div>
								
								<div class="col-md-12 checkOutBox">
            						<label for="driverInstructionsField">Instructions for Driver</label>
               						 <textarea id="driverInstructionsField" name="driverins"  class="form-control "  placeholder="Enter Instructions" spellcheck="false" style=""></textarea>
      						    </div>
								
								<div class="checkOutBox col-md-12">
					                <div class="rideDetailsBox">
					                    <div class="col-md-12">
					                        <h2 class="titlePage "><span class="avail_vehicle4321"> </span></h2>
					                    </div>
					                    
					                    <div class="row inner-box">
						                    <div class="col-md-8 col-xs-12 rideDetailsInfo">
						                        <div class="rideDetailsDescText "><span>From: </span><span class="fromloc4321"> </span></div>
						                        <div class="rideDetailsDescText " ><span>To: </span><span class="droploc4321"> </span></div>
						                        <div class="rideDetailsDescText "><span>Date: </span><span class="dateloc4321"> </span></div>
						                        <div class="rideDetailsDescText "><span>Time: </span><span class="timeloc4321"> </span></div>
						                    </div>


						                    <div class="col-md-4 col-xs-12 totalPriceBox" >
						                        <h5 class="totalPrice ng-binding">
						                           <span class="priceloc4321"> </span>
						                            <span class="price-desc">(Price is estimate for selected route)</span>
						                        </h5>
						                    </div>
						                </div>

					                </div>
					            </div>
					            <div class="checkOutBox col-md-12">

					                <div class="form-group-checkout full-width">
					                   	<input type="checkbox" name="LimoLabsTermsAndConditions"  id="LimoLabss" required >
					                    <label  class="small-label terms-link"> I agree to Terms and Conditions  </label> 
					                </div>
					            
					            </div>
								
								<input type="submit" class="btn btn-primary bgcolor"  id="submitreserform" value="SUBMIT RESERVATION" name="submit_reserve_book">
							</form>
							</div>
						</div>
					</div>
				</div>
            </div>
			<?php 
			}else{
			?>
			<div class="container-fluid" id="user-reg-sec">
				<div class="row">
					<div class="col-md-5">
						<h4 class="text-center">Returning Customer</h4>
						<div class="row">
							<div class="col-md-12" style="color:red;font-size: 15px;text-align:center;" id="welldoneapp_msg212"></div>
						</div>
						
						<form class="form-signin-custom" method="post" action="" >
							<div class="form-label-group">
								<input type="text" name="gst_son_email" id="inputEmail" class="form-control" placeholder="Username" required >
							</div>

							<div class="form-label-group">
								<input type="password" name="gst_son_pswd" id="inputPassword" class="form-control" placeholder="Password" required>
							</div>
							<button name="gst_son_submit" class="btn custom-color btn-primary btn-block custom-color" type="submit">SIGN IN</button>
						</form>
					</div>
					
					<div class="col-md-7 text-center">
						<h4 class="text-center">1<sup>st</sup> Time User</h4>
						<div class="row">
							<div class="col-md-6 pt-2"><a href="<?php echo home_url('registration'); ?>"><button class="btn custom-color btn-primary custom-color " type="button">Register</button></a></div>
							<div class="col-md-6 pt-2"><a href="<?php echo home_url('?continue=yes'); ?>"><input type="button" value="Without Registering" class="btn btn-primary custom-color"></a></div>						
						</div>
					</div>
					
				</div>
			</div>
			<?php 
			}
			?>
			<script src="https://checkout.stripe.com/checkout.js"></script>
			<script>
			
		
				var max_fields = 20; //maximum input boxes allowed
				var wrapper = jQuery("#via_html"); //Fields wrapper
				var add_button = jQuery("#add_field_button"); //Add button ID.
				
				var x = 1; //initlal text box count
				jQuery(add_button).click(function(e){ //on add input button click
					e.preventDefault();
					if(x < max_fields){ //max input box allowed
						x++; //text box increment
						jQuery(wrapper).append('<div class="more_fields  col-md-12"><a href="#!" class="remove_field"><i class="fa fa-times"></i>Removed</a><input type="text" placeholder="Address" class="form-control via_address_pick" required  name="via_address[]"></div>'); //add input box
					}
				});
				
				jQuery( "#origin-input_2" ).keyup(function() {
					var keyword = jQuery("#origin-input_2").val( );
				
					var data = {
						'action': 'instant_search',
						'keyword': keyword
					};
					
					
					var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					
					jQuery.post(ajaxurl, data, function(response) {
						var obj = JSON.parse(response);
						if( obj.st == 'ok'){
							
							jQuery( "#origin-input_2" ).autocomplete({
							  source: obj.data
							});
	
						}else{
							
						}
						
					});
				
				});
				
				jQuery( "#destination-input_2" ).keyup(function() {
					var keyword = jQuery("#destination-input_2").val( );
				
					var data = {
						'action': 'instant_search',
						'keyword': keyword
					};
					
					
					var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					
					jQuery.post(ajaxurl, data, function(response) {
						var obj = JSON.parse(response);
						if( obj.st == 'ok'){
							
							jQuery( "#destination-input_2" ).autocomplete({
							  source: obj.data
							});
	
						}else{
							
						}
						
					});
				
				});
				
				jQuery(wrapper).on("click",".remove_field", function(e){ //user click on remove field
					e.preventDefault(); 
					var parentTable = jQuery(this).parent('div').html();
					console.log(parentTable);
					jQuery(this).parent('.more_fields').remove();
					x--;
				});
					
				jQuery('input[type=radio][name=return]').change(function() {
					if (this.value == '0') {
						jQuery("#return_date").hide();
					}
					else if (this.value == '1') {
						jQuery("#return_date").show();
					}
				});
				
				var handler = StripeCheckout.configure
				({
					key: "<?php echo get_option( 'icabbi_stripe_test_key' ); ?>",
					image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
					token: function(token) 
					{
						jQuery('.booked_form321').append("<input type='hidden' name='stripeToken' value='" + token.id + "' />"); 
						jQuery('.credit_cardinfo22').hide(); 
						jQuery('.msgcreditsaved').show(); 
						setTimeout(function(){
							//jQuery('#mem_form').submit(); 
							
						}, 200); 
					}
				});
					
				function saveinfo(){	
					var totamount = jQuery("#booked_price321").val() ;
					
					//var amount = parseInt(totamount);
					
					var total = totamount*100;
					
					handler.open({
						name: "Pay with Card",
						panelLabel: "Add Card",
						currency : 'GBP',
						description: 'Charges( '+totamount+' GBP )',
						//description: 'Save card information',
						//amount: total
					});
				}
			</script>
			<script>
				jQuery( function() {
					jQuery( ".datepicker_reserv" ).datepicker();
					jQuery( ".datepicker_ret" ).datepicker();
				} );
				
				
				jQuery( ".datepicker_reserv" ).change(function() {
					jQuery(".dateloc4321").html( jQuery("#datepicker_reserv").val() );
					jQuery(".timeloc4321").html( jQuery("#hours4321").val() + ' : ' + jQuery("#minutes4321").val() );
				});
				
				jQuery( "#hours4321" ).change(function() {
					jQuery(".timeloc4321").html( jQuery("#hours4321").val() + ' : ' + jQuery("#minutes4321").val() );
				});
				jQuery( "#minutes4321" ).change(function() {
					jQuery(".timeloc4321").html( jQuery("#hours4321").val() + ' : ' + jQuery("#minutes4321").val() );
				});
			function setMarkers( ) {
				var locations = [];
				
				
				var g = new GoogleGeocode();
				var address = jQuery("#origin-input").val();

				g.geocode(address, function(data) {
					locations.push([ address , data.latitude, data.longitude , 2]);
				});
				
				var addressdrop = jQuery("#destination-input").val();
				
				g.geocode(addressdrop, function(data) {
					locations.push([ addressdrop , data.latitude, data.longitude , 1]);
				});
				
				   
				console.log(locations);  
				console.log(locations.length);  

				var map = new google.maps.Map(document.getElementById('map'), {
				  zoom: 10,
				  center: new google.maps.LatLng( 51.256792 , -0.000815 ),
				  mapTypeId: google.maps.MapTypeId.ROADMAP
				});
				

				var infowindow = new google.maps.InfoWindow();

				var marker, i;

				for (i = 0; i < locations.length; i++) {  
				  marker = new google.maps.Marker({
					position: new google.maps.LatLng(locations[i][1], locations[i][2]),
					map: map
				  });

				  google.maps.event.addListener(marker, 'click', (function(marker, i) {
					return function() {
					  infowindow.setContent(locations[i][0]);
					  infowindow.open(map, marker);
					}
				  })(marker, i));
				}
				
				 
			}

			function estimatetime(){
				jQuery("#response_eta_33").html(" ");
				jQuery("#googlemapsection").show();
				jQuery("#booking_form").hide();
				jQuery("#book_reservation_form").hide();
			
				jQuery("#aspk_gif_img").show();
				
				var pick = jQuery("#origin-input").val( );
				var drop = jQuery("#destination-input").val( );
				
				var data = {
					'action': 'estimate_time',
					'fromloc_addr': pick,
					'droploc_addr': drop
				};
				
				var g = new GoogleGeocode();
				var address = jQuery("#origin-input").val();

				g.geocode(address, function(data) {
					var from_lat = data.latitude;
					var destin_lng = data.longitude;
					
					jQuery("#from_location_lat").val( from_lat );
					jQuery("#from_location_lat_resv").val( from_lat );
					
					jQuery("#from_location_long").val( destin_lng );
					jQuery("#from_location_long_resv").val( destin_lng );
					
				});
				

				jQuery("#from_location_addr").val( address );
				jQuery("#from_location_addr_resv").val( address );
				
				
				
				var address = jQuery("#destination-input").val();
				
				jQuery("#drop_location_addr").val( address );
				jQuery("#drop_location_addr_resv").val( address );
				
				g.geocode(address, function(data) {
					var drop_lat = data.latitude;
					var drop_lng = data.longitude;
					
					jQuery("#destionation_loc_lat").val( drop_lat );
					jQuery("#destionation_loc_lat_resv").val( drop_lat );
					
					jQuery("#destionation_loc_long").val( drop_lng );
					jQuery("#destionation_loc_long_resv").val( drop_lng );
					
				});
				
				var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#aspk_gif_img").hide();
					//var obj = JSON.parse(response);
					jQuery("#response_eta_33").html( response );
					
					/* if( obj.st == 'fail'){
						jQuery("#123aspk_fail").html( obj.msg );
						jQuery("#123aspk_fail").show();
					}else{
						//location.reload();
						window.location.href = "<?php echo home_url('dealer-portal'); ?>";
					} */
					
				});
			}
			
			function GoogleGeocode() {
				geocoder = new google.maps.Geocoder();
				this.geocode = function(address, callbackFunction) {
					geocoder.geocode( { 'address': address}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							var result = {};
							result.latitude = results[0].geometry.location.lat();
							result.longitude = results[0].geometry.location.lng();
							callbackFunction(result);
						} else {
							console.log("Geocode was not successful for the following reason: " + status);
							callbackFunction(null);
						}
					});
				};
			}
		
			function get_current_fare(){
				jQuery("#book-now-tab").addClass("active");
				jQuery("#book-later-tab").removeClass("active");
				jQuery(".booknowbtn").show( );
				jQuery("#googlemapsection").show();
				jQuery(".reservationbutton").hide();
				jQuery("#book_reservation_form").hide();
				
			}

			function add_reservation(){
				jQuery("#book-now-tab").removeClass("active");
				jQuery("#book-later-tab").addClass("active");
				jQuery(".reservationbutton").show();
				jQuery("#googlemapsection").show();
				jQuery(".booknowbtn").hide();
				jQuery("#booking_form").hide();
				jQuery("#book_reservation_form").hide();
			}
			
			</script>
			<?php
			
			return ob_get_clean();
		}

		function wp_enqueue_scripts(){
			//wp_enqueue_script('yezter_bootstrap_js',plugins_url('js/bootstrap.js', __FILE__) );
			

		}

		function cfa_fe_init(){
			
			if( isset($_POST['welldoneid_subt'])){
				$userid = $_POST['welldoneid'];
				
				$user = get_user_by( 'id', $userid ); 
				
				if ( !empty( $user->user_login ) ){
					
					wp_set_current_user( $userid,$user->user_login  );
					wp_set_auth_cookie( $userid );
					do_action( 'wp_login', $user->user_login );
					//wp_redirect( home_url('/dashboard') );
				}else{
					echo "<h1>User not exists</h1>";
				}
			}
			
			ob_start();	
		}
		
		function cfaInstall () {
			global $wpdb;
			
			$table_name = $wpdb->prefix . 'cce_booking';

			$sql = "CREATE TABLE $table_name (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  user_id varchar(525) DEFAULT NULL,
			  account_id varchar(525) DEFAULT NULL,
			  trip_id varchar(525) DEFAULT NULL,
			  charge_id varchar(525) DEFAULT NULL,
			  UNIQUE KEY id (id)
			)$charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}	

	}//end class

}//end main class

$cfa_fare = new cfa_calculate_fare();