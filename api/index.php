<?php

require_once 'Slim/Slim.php';
		
require_once "DottifyManager.php" ;
require_once "Tuple.php" ;
require_once "OptionManager.php" ;


use Slim\Slim ;

\Slim\Slim::registerAutoloader();

session_start() ;
$app = new \Slim\Slim();
$app->contentType('application/json');
// -------------------------------------
// Routes
$app->get( '/test/ping', 'testping');
$app->get( '/user', 'listusers' ) ;
$app->get( '/user/login/:username/:password', 'loginuser') ;
$app->get( '/user/byemail','getuserbyemail');
$app->get( '/user/basesurvey/:id/:ver', 'getbasesurvey' ) ;
$app->post( '/user/basesurvey', 'addbasesurvey' ) ;
//$app->get( '/user/zip/:zip', 'listusersinzip' ) ;
$app->post( '/user/validate', 'validateuser' ) ;
$app->get( '/user/validate/:uuid', 'revalidateuser' ) ;
$app->get( '/user/sendlink', 'emailUserLink') ;
$app->post( '/user/sendlink', 'emailUserLink') ;
$app->post( '/user/position', 'updateUserPosition') ;
$app->get( '/user/:uuid', 'getuser' ) ;
$app->post( '/user', 'createuser' ) ;
$app->put( '/user', 'updateuser' ) ;
$app->delete( '/user/:uuid', 'deleteuser' ) ;
$app->get( '/zipcode', 'listzipcodes') ;
$app->get( '/zipcode/:zip', 'getzipinfo') ;
$app->get( '/ntdsuser', 'listNTDSUsers') ;
$app->get( '/usersession/logout', 'logout' ) ;
$app->get( '/usersession/:uuid', 'getUserSessionInfo') ;
$app->get( '/netinfluencers', 'getNetInfluencers');
$app->get( '/country', 'listcountries') ;
$app->get( '/country/:isoid', 'getcountry' );
$app->get( '/stats/taglist', 'getTagList') ;
$app->get( '/stats/usersperzip', 'getUsersPerZip');
$app->get( '/metadata/:model', 'getModelMetadata' ) ;

// --------------------------------------------------------

$app->get( '/incident/:id', 'getIncident') ;
$app->get( '/incident', 'listIncidents');
$app->post( '/incident', 'createIncident');
$app->put( '/incident', 'updateIncident') ;
$app->delete( '/incident/:id', 'deleteIncident');
// --------------------------------------------------------
$app->get( '/comment/:id', 'getComment') ;
$app->get( '/comment', 'listComments');
$app->post( '/comment', 'createComment');
$app->put( '/comment', 'updateComment') ;
$app->delete( '/comment/:id', 'deleteComment');
// --------------------------------------------------------
$app->get( '/reltype/:id', 'getRelType') ;
$app->get( '/reltype', 'listRelTypes');
// --------------------------------------------------------
$app->get( '/link/obj', 'listLinkObjs' );
$app->get( '/link/:id', 'getLink') ;
$app->get( '/link', 'listLinks');
$app->post( '/link', 'createLink') ;
$app->put( '/link', 'updateLink') ;
$app->delete( '/link/:id', 'deleteLink') ;

// --------------------------------------
$app->run();

// dispatch the calls to the Manager to separate concerns
//

function testping() {
	echo "testping\n" ;
}

function listusers() {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get("limit") ;
	$preds = Tuple::parseTriTuple($request->get("preds")) ;
	$order = Tuple::parseTuple( $request->get("order")) ;
	$result = $mgr->listusers($offset, $limit, $preds, $order) ;
	send( $result, $start ) ;

}

function getuser($uuid) {

	$start = microtime() ;
	$mgr = new DottifyManager() ;

	send( $mgr->getUserByUuid($uuid) , $start) ;
}

function getuserbyemail() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$mgr = new DottifyManager() ;
	$email = $request->params("email") ;
	$magic = $request->params("magic") ;
	if( $magic == "__MAGICCOOKIE__") {
		send( $mgr->getUserByEmail($email) , $start) ;
	}
}

function loginuser($username, $password) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;

	send( $mgr->loginUser($username, $password) , $start) ;
}

function createuser() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$adopt = $request->params("adopt") ;
	$adopt = true ;
	$mgr = new DottifyManager() ;
	$userout = $mgr->createUser( $user, $adopt ) ;
	send( $userout, $start) ;
}

function validateuser() {
	
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$mode = $request->params("mode") ;
	$attributes = $request->params( "attr" ) ;
	if( !empty( $attributes)) {
		$attributes = explode( ',', $attributes) ;
	}
	
	$mgr = new DottifyManager() ;
	$validout = $mgr->validateUser( $user, $attributes, $mode ) ;
	send( $validout, $start) ;
	
}

function revalidateuser( $uuid ) {

	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$attributes = $request->params( "attr" ) ;
	if( !empty( $attributes)) {
		$attributes = explode( ',', $attributes) ;
	}
	
	$mgr = new DottifyManager() ;
	$validout = $mgr->reValidateUser( $uuid, $attributes ) ;
	send( $validout, $start) ;
}

function updateuser() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$norev = $request->params("norev") ;
	$mgr = new DottifyManager() ;
	$userout = $mgr->updateUser( $user, $norev ) ;
	send( $userout, $start) ;
}

function updateUserPosition() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$mgr = new DottifyManager() ;
	$userout = $mgr->updateUserPosition( $user ) ;
	send( $userout, $start) ;
}

function deleteuser($uuid) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$resp = $mgr->deleteUser( $uuid ) ;
	send( $resp, $start) ;


}

function addbasesurvey() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$survey = json_decode($request->getBody());
	$mgr = new DottifyManager() ;
	$surveyout = $mgr->addBaseSurvey( $survey ) ;
	send( $surveyout, $start) ;
}

function getbasesurvey($id, $ver) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;

	send( $mgr->getBaseSurvey($id, $ver), $start) ;
}

/*
function listusersinzip($zipcode) {
	
	$sql = "SELECT * FROM user WHERE zip = :zip ORDER BY created";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("zipcode", $zipcode);
		$stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"users": ' . json_encode($users) . '}';
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}
*/

function listzipcodes( ) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get( "limit" ) ;

	$mgr = new DottifyManager() ;
	$out = $mgr->listZipcodes( $offset, $limit ) ;
	send( $out, $start) ;
	

}

function getzipinfo($zipcode) {
	$start = microtime() ;

	$mgr = new DottifyManager() ;
	$out = $mgr->getZipcodeInfo( $zipcode ) ;
	send( $out, $start) ;
}

function listNTDSUsers() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get( "limit" ) ;
	$state = $request->get( "state" ) ;
	
	$mgr = new DottifyManager() ;
	$out = $mgr->listNTDSUsers( $state, $offset, $limit ) ;
	send( $out, $start) ;	
	
	
}

function getUserSessionInfo( $uuid ) {

	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$info = $mgr->getUserSessionInfo( $uuid ) ;
	
	send( $info, $start ) ;
	
}

function logout(  ) {

	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$info = $mgr->logout( ) ;

	send( $info, $start ) ;

}

function getNetInfluencers() {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$info = $mgr->getInfluencers( ) ;
	
	send( $info, $start ) ;	
}

function emailUserLink(  ) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$request = Slim::getInstance()->request();
	$email = $request->get( "email" ) ;
	$info = $mgr->emailUserLink( $email ) ;
	
	send( $info, $start ) ;
}

function listcountries() {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$result = $mgr->listCountries() ;
	send( $result, $start ) ;
}

function getcountry($isoid) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$result = $mgr->getCountry($isoid) ;
	send( $result, $start ) ;
}

function getTagList() {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$request = Slim::getInstance()->request();
	$raw = $request->get( "raw" ) ;
	$result = $mgr->getTagList($raw) ;
	send( $result, $start ) ;	
}

function getUsersPerZip() {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$request = Slim::getInstance()->request();
	$country = $request->get( "country" ) ;
	$result = $mgr->getUsersPerZip($country) ;
	send( $result, $start ) ;	
}


function getModelMetadata($modelName) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$result = $mgr->getModelMetadata($modelName) ;
	send( $result, $start ) ;
}

// -------------------------------------------------------------
// Incident dispatches

function getIncident( $id ) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$ver = $request->get( "ver" ) ;
	$mgr = new DottifyManager() ;
	send( $mgr->getIncident($id, $ver) , $start) ;
	
}

function listIncidents( ) {
	
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get("limit") ;
	$preds = Tuple::parseTriTuple($request->get("preds")) ;
	$order = Tuple::parseTuple( $request->get("order")) ;
	$result = $mgr->listIncidents($offset, $limit, $preds, $order) ;
	
	send( $result, $start ) ;
}

function createIncident() {
	
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$data = json_decode($request->getBody());
	$mgr = new DottifyManager() ;
	$result = $mgr->createIncident( $data ) ;
	send( $result , $start) ;
}

function updateIncident() {

	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$data = json_decode($request->getBody());
	$norev = $request->params("norev") ;
	$mgr = new DottifyManager() ;
	$result = $mgr->updateIncident( $data, $norev ) ;
	send( $result, $start) ;

}

function deleteIncident($id) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$mgr = new DottifyManager() ;
	send( $mgr->deleteIncident($id) , $start) ;
}

// -------------------------------------------------------------

function getComment( $id ) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	//$ver = $request->get( "ver" ) ;
	$ver = null ;
	$mgr = new DottifyManager() ;
	send( $mgr->getComment($id, $ver) , $start) ;

}

function listComments( ) {

	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get("limit") ;
	$preds = Tuple::parseTriTuple($request->get("preds")) ;
	$order = Tuple::parseTuple( $request->get("order")) ;
	$result = $mgr->listComments($offset, $limit, $preds, $order) ;

	send( $result, $start ) ;
}

function createComment() {

	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$data = json_decode($request->getBody());
	$mgr = new DottifyManager() ;
	$result = $mgr->createComment( $data ) ;
	send( $result , $start) ;
}

function updateComment() {

	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$data = json_decode($request->getBody());
	$norev = $request->params("norev") ;
	$mgr = new DottifyManager() ;
	$result = $mgr->updateComment( $data, $norev ) ;
	send( $result, $start) ;

}

function deleteComment($id) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$mgr = new DottifyManager() ;
	send( $mgr->deleteComment($id) , $start) ;
}
// --------------------------------------------------------------
function getRelType($id) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$mgr = new DottifyManager() ;
	send( $mgr->getRelType($id) , $start) ;
	
}
function listRelTypes( ) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$parent = $request->get( "parent" ) ;
	$include = $request->get( "include");
	$norecurse = $request->get( "norecurse");
	$mgr = new DottifyManager() ;
	send( $mgr->listRelTypes($parent, $include, $norecurse) , $start) ;
}

function getLink($id ) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	send( $mgr->getLink($id), $start) ;
}

function listLinks() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$origin = $request->get( "origin" ) ;
	$dir = $request->get( "dir");
	$reltype = $request->get( "reltype");
	$offset = $request->get( "offset");
	$limit = $request->get( "limit");
	$mgr = new DottifyManager() ;
	send( $mgr->listLinks($offset, $limit, $origin, $dir, $reltype) , $start) ;
}

function listLinkObjs() {
	
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$origin = $request->get( "origin" ) ;
	$dir = $request->get( "dir");
	$reltype = $request->get( "reltype");
	$offset = $request->get( "offset");
	$limit = $request->get( "limit");
	$withlink = $request->get( "withlink");
	$mgr = new DottifyManager() ;
	send( $mgr->listLinkObjs($offset, $limit, $origin, $dir, $reltype, $withlink) , $start) ;
}

function createLink() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$data = json_decode($request->getBody());

	$mgr = new DottifyManager() ;
	$result = $mgr->createLink( $data ) ;
	send( $result , $start) ;
}

function updateLink() {
	send( "update link", null) ;
}

function deleteLink($id) {
	send( "unlink $id", null) ;
}

// --------------------------------------------------------------
function send( $result, $start ) {
	$elapsed = microtime() - $start ;
	// $result->elapsed = $elapsed ;
	header( "Content-Type: application/json") ;
	echo json_encode($result) ;
	
}



