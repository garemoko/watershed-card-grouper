<pre><?php

include ("WatershedPHP/watershed.php");
include ("config.php");

$wsclient = new \WatershedClient\Watershed("https://sandbox.watershedlrs.com", $auth, $orgId, $dashboard);

// participant group type id = 1
// participant group id = 1
// clerk group id = 2

// performance group type id = 2
// mostRecalls = 22
// leastRecalls = 12
// mostPhoneCalls = 3
// leastPhoneCalls = 23

$people = getAllPeople($wsclient, $orgId);
$participants = [];
$clerks = [];

foreach ($people as $person) {
  if(!isset($person->name)){
    continue;
  }
  if (strpos($person->name, 'Part') !== false) {
    array_push($participants, $person->id);
  } elseif (strpos($person->name, 'Clerk') !== false) {
    array_push($clerks, $person->id);
  }
}

$wsclient->deleteGroup($orgId, 4);
$wsclient->deleteGroup($orgId, 13);
$wsclient->deleteGroup($orgId, 5);
$wsclient->deleteGroup($orgId, 6);

updateGroupMembership($wsclient, $orgId, 'clerks', $clerks);
updateGroupMembership($wsclient, $orgId, 'participants', $participants);

$response = $wsclient->getCardData($orgId, '25308', 'heatmap', true, '-', '1', '10');
$mostRecalls = getPeopleIdsFromCardData($response);
updateGroupMembership($wsclient, $orgId, 'most paitent recalls', $mostRecalls);

$response = $wsclient->getCardData($orgId, '25308', 'heatmap', true, '+', '1', '10');
$leastRecalls = getPeopleIdsFromCardData($response);
updateGroupMembership($wsclient, $orgId, 'least paitent recalls', $leastRecalls);

$response = $wsclient->getCardData($orgId, '25308', 'heatmap', true, '-', '2', '10');
$mostPhoneCalls = getPeopleIdsFromCardData($response);
updateGroupMembership($wsclient, $orgId, 'most phone calls', $mostPhoneCalls);

$response = $wsclient->getCardData($orgId, '25308', 'heatmap', true, '+', '2', '10');
$leastPhoneCalls = getPeopleIdsFromCardData($response);
updateGroupMembership($wsclient, $orgId, 'least phone calls', $leastPhoneCalls);


function getAllPeople($wsclient, $orgId, $pointer = 0){
  $response = $wsclient->sendRequest(
      "GET", 
      'organizations/'.$orgId.'/people?_offset='.$pointer
  );

  $content = json_decode($response["content"]);
  $allCount = $content->count;
  $results = $content->results;

  $pointer += count($results);

  if ($pointer < $allCount ){
    $results = array_merge($results, getAllPeople($wsclient, $orgId, $pointer));
  }

  return $results;

}

function updateGroupMembership($wsclient, $orgId, $groupName, $newMembers){
  $response = $wsclient->getGroupsByName($orgId, $groupName);
  $group = $response["groups"][0];
  $group->peopleIds = $newMembers;
  unset($group->people);
  unset($group->peopleCustomIds);
  return $wsclient->updateGroup($orgId, $group->id, $group);
}

function getPeopleIdsFromCardData($response){
  $return = [];
  foreach ($response['content']->results[0]->result->data as $row) {
    array_push($return, $row->values[0]->value);
  }
  return $return;
}