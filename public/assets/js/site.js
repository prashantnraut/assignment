jQuery(document).ready(function(){
   if(localStorage.issuedata){
    var editData = JSON.parse(localStorage.issuedata);
        editData = editData[0];
    if (editData) {
        $("#subject").val(editData.subject);
        $("#due_date").val(editData.due_date);
        var region_id_arr = editData.region_ids.split(',');
        $(".region_id").each(function(index) {
            var val = $(this).val();
            if (region_id_arr.includes(val)) {
              $(this).prop('checked', true);
            }
          });
          $(".priority_id").each(function(index) {
            var val = $(this).val();
            if (editData.priority_id ==val) {
              $(this).prop('checked', true);
            }
          });

        $("#status_id").val(editData.status_id);
        $("#description").val(editData.description);
        $("#assignee_id").val(editData.assignee_id);
        $("#reviewer_id").val(editData.reviewer_id);
        $("#target_version_id").val(editData.target_version_id);
        $("#reviewer_id").val(editData.reviewer_id);
        $("#reviwer_comments").val(editData.reviewer_comments);
        $("#issue_id").val(editData.issue_id);
        localStorage.clear();
        }
    }

});

//SET all data into issue form
$( document ).ready(function() {
    $('.edit_issue').on('click',function() {

    var issueid = $(this).attr('rel');
    if(!issueid || issueid == '') {
        alert('Error in processing .');
        return false;
    }
 
    var detailsurl = '/edit-issue'; 

        $.ajax({
            url: detailsurl,
            method :'post',
            data: 'issueids='+issueid,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'OK') {
                    localStorage.clear();
                    if(data.issuedata){
                        localStorage.issuedata = JSON.stringify(data.issuedata); 
                    }                                        
                    window.location.replace("/edit-issue-form");
                } else {
                    alert('Error in processing .');
                }

            },
            error: function(){
                alert('Error in processing ...');
            },
        });
    });
});


// on reset btn click
$('#reset_btn').on('click',function() {
    location.reload();
});

// on cancel btn click
$('#cancel_btn').on('click',function() {
    window.location.replace("/issue-list");
    localStorage.clear();
});

//isseus form validation
$("#addissueform").validate({      
    rules: {
      subject: "required",
      status_id: "required",
      due_date: "required",
      assignee_id: "required",
      reviewer_id: "required",
      target_version_id: "required",
      image_name: "required",
    },
    messages: {
        subject: "Please enter subject",
        status_id: "Please select status",
        due_date: "Please select due date",
        assignee_id: "Please select assignee",
        reviewer_id: "Please select reviewer",
        target_version_id: "Please select target version",
        image_name: "Please select image",
    },   
  });

// check subject is unique or not
$('#subject').on('change',function() {

    var subjectVal = $(this).val();
     if(subjectVal == '') {
        return false;
     }   

    var checkurl = '/check-issubject-exist';

        $.ajax({
            url: checkurl,
            method :'post',
            data: 'subject='+subjectVal,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'OK') {
                    return true;
                } else {
                    alert('Subject already exists , Subject should be unique.');
                    $('#subject').focus();
                }
            },
            error: function(){
                alert('Error in processing !');
            },
        });
});



// Add issue on submit btn click
$('#submit_btn').on('click',function() {
   
    if($("#addissueform").valid()) {

    var formData = $('#addissueform').serialize();

    var addurl = '/add-issue';

        $.ajax({
            url: addurl,
            method :'post',
            data: formData,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'OK') {
                    alert('Added successfully !');
                    window.location.replace("/issue-list");
                } else {
                    alert('Error in processing !');
                }
            },
            error: function(){
                alert('Error in processing !');
            },
        });
    }
});


// on submit btn click
$('.delete_issue').on('click',function() {
    var issueids = [];

    $.each($(".issue_id_chk:checked"), function(){
        issueids.push($(this).attr('rel'));
    });

    if(issueids.length == 0) {
        alert('Please select issue for action. ');
    } 

    if(issueids.length > 0 && confirm("Are you sure !")) { 

    issueids = issueids.join(", ");
 
    var deleteurl = '/delete-issue'; 

        $.ajax({
            url: deleteurl,
            method :'post',
            data: 'issueids='+issueids,
            dataType: 'json',
            success: function (data) {

                if (data.status == 'OK') {
                    alert('Deleted successfully .');
                    location.reload();
                } else {
                    alert('Error in processing .');
                }
            },
            error: function(){
                alert('Error in processing .');
            },
        });
    } 
});



// get form data by ajax
$('.edit_issue').on('click',function() {

    var issueid = $(this).attr('rel');
    if(!issueid || issueid == '') {
        alert('Error in processing .');
        return false;
    }
 
    var detailsurl = '/edit-issue'; 

        $.ajax({
            url: detailsurl,
            method :'post',
            data: 'issueids='+issueid,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'OK') {                    
                    return true;
                } else {
                    alert('Error in processing .');
                    return false;
                }
            },
            error: function(){
                alert('Error in processing ...');
            },
        });        
});


// update issue on submit btn click
$('#update_btn').on('click',function() {
   
    if($("#addissueform").valid()) {

    var formData = $('#addissueform').serialize();

    var addurl = '/update-issue';

        $.ajax({
            url: addurl,
            method :'post',
            data: formData,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'OK') {
                    alert('Updated successfully !');
                    window.location.replace("/issue-list");
                } else {
                    alert('Error in processing !');
                }
            },
            error: function(){
                alert('Error in processing !');
            },
        });    
    }
});