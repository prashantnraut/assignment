<?php
 
    //Home page redirection
    $app->get('/', function ($request, $response, $args) {        
        $response = $this->renderer->render($response, 'index.phtml', ['content' => 'home']);
        return $response;
    });
    
    // get issue form dropdown & related data on form load
    $app->get('/add-issue-form', function ($request, $response, $args) {  

        $sqltv = "SELECT `target_version_id`, `target_version` FROM `target_version` WHERE 
        `is_deleted` = 0 ";
        $row = $this->db->prepare($sqltv);
        $row->execute();
        $targetlist = $row->fetchAll();
  
        $sqlrew = "SELECT `reviewer_id`, `reviewer_name` FROM
        `reviewer` WHERE `is_deleted` = 0 ";
        $row = $this->db->prepare($sqlrew);
        $row->execute();
        $reviewerlist = $row->fetchAll();

        $sqlasgn = "SELECT `assignee_id`, `assignee_name` FROM
        `assignee` WHERE `is_deleted` = 0";
        $row = $this->db->prepare($sqlasgn);
        $row->execute();
        $assigneelist = $row->fetchAll();

        $editissuedata['issuedata'] = isset($issuedata[0]) && count($issuedata[0]) > 0 ? $issuedata[0] : array();
        $editissuedata['targetlist'] = $targetlist;
        $editissuedata['reviewerlist'] = $reviewerlist;
        $editissuedata['assigneelist'] = $assigneelist;

        $response = $this->renderer->render($response, 'index.phtml', ['content' => 'form','formtype' => 'Add','editissuedata'=>$editissuedata]);
        return $response;
    });

    //GET issues list 
    $app->get('/issue-list', function ($request, $response, $args) {
        $issuelist = array();
        
        try{

        $sqlS = "SELECT `issue_id`, `subject`, `description`, STS.status_title, PRI.priority_title , `due_date`, ASGN.assignee_name , REW.reviewer_name ,  TV.target_version FROM `issues` ISS
                LEFT JOIN   status STS ON ISS.status_id = STS.status_id  
                LEFT JOIN   priority PRI ON ISS.priority_id = PRI.priority_id  
                LEFT JOIN   assignee ASGN ON ISS.assignee_id = ASGN.assignee_id  
                LEFT JOIN   reviewer REW ON ISS.reviewer_id = REW.reviewer_id  
                LEFT JOIN   target_version TV ON ISS.target_version_id = TV.target_version_id  
                WHERE ISS.`is_deleted` = 0 ORDER BY issue_id";

        $row = $this->db->prepare($sqlS);
        $row->execute();
        $issuelist = $row->fetchAll();
        $response = $this->renderer->render($response, 'index.phtml', ['content' => 'list','issuelist' => $issuelist]);
        return $response;
        
        }catch(PDOException $e) {

            $response = $this->renderer->render($response, 'index.phtml', ['content' => 'list','issuelist' => $issuelist]);
            return $response;          

        }

    });
    //END - GET issues list 
    
    //Check subject already exists or not
    $app->post('/check-issubject-exist', function ($request, $response, $args) {
        
        $postData = $request->getParsedBody(); 
        
        $subject = trim($postData['subject']);        
        
        $subject =  preg_replace('/[^A-Za-z0-9\-]/', ' ', $subject); // Removes special chars.
        
        $rerunArr['status'] = "ERR";
       
        try{

            $sqlS = "SELECT COUNT(`issue_id`) as count FROM `issues` WHERE `subject` like '$subject' ORDER BY issue_id";
            $row = $this->db->prepare($sqlS);
            $row->execute();
            $issuelist = $row->fetchAll();
            
            if(isset($issuelist[0]) && isset($issuelist[0]['count']) && $issuelist[0]['count'] > 0 ) 
            {
                $rerunArr['status'] = "ERR";

            } else {
                $rerunArr['status'] = "OK";
                //$rerunArr['count'] = $issuelist;
            }

        }catch(PDOException $e) {
             // show error details 
             $rerunArr['status'] = $e->getMessage();             
        }

        return $this->response->withJson($rerunArr);

    });
    //END - Check subject already exists or not
    
     // Delete issues record 
     $app->post('/delete-issue', function ($request, $response, $args) {
        $ids = $request->getParsedBody(); 
        $rerunArr['status'] = "ERR";

        try{
            if(!isset($ids['issueids']) || $ids['issueids'] == '' || empty($ids['issueids'])) {
                $rerunArr['status'] = "ERR";
                return $this->response->withJson($rerunArr);
            }

            $issueids = $ids['issueids'];
            $sqlU = "UPDATE `issues` SET is_deleted = 1  WHERE `issue_id` IN ( $issueids )";
            $query = $this->db->prepare($sqlU);
            $query->execute();          
            $rerunArr['status'] = "OK";

        }catch(PDOException $e) {
            // show error details 
            $rerunArr['status'] = $e->getMessage();            
        }
       
        return $this->response->withJson($rerunArr);

    });
    //END - Delete issues record 

    //GET issue form related data
    $app->get('/edit-issue-form', function($request, $response, $args) {
        $editissuedata = array();
        
        $sqltv = "SELECT `target_version_id`, `target_version` FROM `target_version` WHERE 
        `is_deleted` = 0 ";
        $row = $this->db->prepare($sqltv);
        $row->execute();
        $targetlist = $row->fetchAll();

        $sqlrew = "SELECT `reviewer_id`, `reviewer_name` FROM
        `reviewer` WHERE `is_deleted` = 0 ";
        $row = $this->db->prepare($sqlrew);
        $row->execute();
        $reviewerlist = $row->fetchAll();

        $sqlasgn = "SELECT `assignee_id`, `assignee_name` FROM
        `assignee` WHERE `is_deleted` = 0";
        $row = $this->db->prepare($sqlasgn);
        $row->execute();
        $assigneelist = $row->fetchAll();
          
        $editissuedata['issuedata'] = isset($issuedata[0]) && count($issuedata[0]) > 0 ? $issuedata[0] : array();
        $editissuedata['targetlist'] = $targetlist;
        $editissuedata['reviewerlist'] = $reviewerlist;
        $editissuedata['assigneelist'] = $assigneelist;

        $response = $this->renderer->render($response, 'index.phtml', ['content' => 'form','formtype' => 'Edit','editissuedata'=>$editissuedata]);
        return $response;

     });
     
    //POST - Add new issue 
    $app->post('/add-issue', function ($request, $response, $args) {
        $postdata = $request->getParsedBody();  

        $rerunArr['status'] = "ERR";
        
        if(!isset($postdata) || !is_array($postdata)) 
        {
            return $rerunArr['status'];
        }

        $subject = isset($postdata['subject']) ? trim($postdata['subject']):'';
        $description = isset($postdata['description']) ? trim($postdata['description']):'';
        $status_id = isset($postdata['status_id']) ? trim($postdata['status_id']):'';
        $priority_id = isset($postdata['priority_id']) ? trim($postdata['priority_id']):'';
        $region_ids = isset($postdata['region_id']) ? trim(implode(',',$postdata['region_id'])):'';
        $due_date = isset($postdata['due_date']) ? date("Y-m-d h:i:s", strtotime(trim($postdata['due_date']))):'';
        $assignee_id = isset($postdata['assignee_id']) ? trim($postdata['assignee_id']):'';
        $reviewer_id = isset($postdata['reviewer_id']) ? trim($postdata['reviewer_id']):'';
        $target_version_id = isset($postdata['target_version_id']) ? trim($postdata['target_version_id']):'';
        $image_name = isset($postdata['image_name']) ? trim($postdata['image_name']):'';
        $reviewer_comments = isset($postdata['reviwer_comments']) ? trim($postdata['reviwer_comments']):'';

        try{

        $sqlI = "INSERT INTO `issues`(`subject`, `description`, `status_id`, `priority_id`,`region_ids`, `due_date`, `assignee_id`, `reviewer_id`, `target_version_id`, `image_name`,`reviewer_comments`, `date_added`) VALUES ('$subject','$description',$status_id,$priority_id,'$region_ids','$due_date',$assignee_id,$reviewer_id,'$target_version_id','$image_name','$reviewer_comments',NOW())";
        $query = $this->db->prepare($sqlI);

        $query->execute();          
        $rerunArr['status'] = "OK";

        }catch(PDOException $e) {
            // show error details 
            $rerunArr['status'] = $e->getMessage();            
        }

        return $this->response->withJson($rerunArr);

    });



    // GET - all issue details issues record 
    $app->post('/edit-issue', function ($request, $response, $args) {
        $ids = $request->getParsedBody(); 
        $rerunArr['status'] = "ERR";

        try{
            if(!isset($ids['issueids']) || $ids['issueids'] == '' || empty($ids['issueids'])) {
                $rerunArr['status'] = "ERR";
                return $this->response->withJson($rerunArr);
            }

            $issueid = $ids['issueids'];

            $sqlS = "SELECT `issue_id`, `subject`, `description`, `status_id`, `priority_id`, 
                `region_ids`, `due_date`, `assignee_id`, `reviewer_id`, `target_version_id`, `image_name`,
                `reviewer_comments`, `date_added`, `date_modified`, `is_deleted` FROM `issues`   
                WHERE `is_deleted` = 0 AND issue_id = $issueid ";
                
            $row = $this->db->prepare($sqlS);
            $row->execute();
            $issuedata = $row->fetchAll();
            if(count($issuedata) > 0) {
                $rerunArr['issuedata'] = $issuedata;
                $rerunArr['status'] = "OK";
            }
            

        }catch(PDOException $e) {
            // show error details 
            $rerunArr['status'] = $e->getMessage();            
        }

        return $this->response->withJson($rerunArr);

    });
    //END - get all issue details  


    //update new issue 
    $app->post('/update-issue', function ($request, $response, $args) {
        $postdata = $request->getParsedBody();  

        $rerunArr['status'] = "ERR";
        
        if(!isset($postdata) || !is_array($postdata)) 
        {
            return $rerunArr['status'];
        }
        $issue_id = isset($postdata['issue_id']) ? trim($postdata['issue_id']):'';
        $subject = isset($postdata['subject']) ? trim($postdata['subject']):'';
        $description = isset($postdata['description']) ? trim($postdata['description']):'';
        $status_id = isset($postdata['status_id']) ? trim($postdata['status_id']):'';
        $priority_id = isset($postdata['priority_id']) ? trim($postdata['priority_id']):'';
        $region_ids = isset($postdata['region_id']) ? trim(implode(',',$postdata['region_id'])):'';
        $due_date = isset($postdata['due_date']) ? date("Y-m-d h:i:s", strtotime(trim($postdata['due_date']))):'';
        $assignee_id = isset($postdata['assignee_id']) ? trim($postdata['assignee_id']):'';
        $reviewer_id = isset($postdata['reviewer_id']) ? trim($postdata['reviewer_id']):'';
        $target_version_id = isset($postdata['target_version_id']) ? trim($postdata['target_version_id']):'';
        $image_name = isset($postdata['image_name']) ? trim($postdata['image_name']):'';
        $reviewer_comments = isset($postdata['reviwer_comments']) ? trim($postdata['reviwer_comments']):'';

        try{
            
        $sqlU = "UPDATE `issues` SET `subject`= '$subject',`description`='$description',`status_id`='$status_id',`priority_id`='$priority_id',`region_ids`='$region_ids',`due_date`='$due_date',`assignee_id`=$assignee_id,`reviewer_id`=$reviewer_id,`target_version_id`=$target_version_id,`image_name`='$image_name',`reviewer_comments`='$reviewer_comments',`date_modified`=NOW() WHERE issue_id = $issue_id";
        $query = $this->db->prepare($sqlU);

        $query->execute();          
        $rerunArr['status'] = "OK";

        }catch(PDOException $e) {
            // show error details 
            $rerunArr['status'] = $e->getMessage();            
        }

        return $this->response->withJson($rerunArr);

    });