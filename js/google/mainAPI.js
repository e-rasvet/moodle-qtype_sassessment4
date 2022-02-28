
var typeRoot = M.cfg.wwwroot + "/question/type/sassessment";
var recStatus = 0;
var recData = [];
var audioTrack;
var audioTrackFile;

function recBtnGoogleAPI(ev) {
    var btn = ev.target;
    var id = btn.name;


    recData[id] = new Object();
    recData[id].btn = btn;
    recData[id].qid = btn.getAttribute('qid');
    recData[id].usageid = btn.getAttribute('usageid');
    recData[id].slot = btn.getAttribute('slot');
    recData[id].auto_score = btn.getAttribute('auto_score');
    recData[id].targetanswerid = document.getElementById(btn.getAttribute('targetanswerid'));
    recData[id].ans = document.getElementById(btn.getAttribute('answername'));
    recData[id].ansDiv = document.getElementById(btn.getAttribute('answerDiv'));
    recData[id].grade = document.getElementById(btn.getAttribute('gradename'));
    recData[id].mediaelement = document.getElementById(btn.getAttribute('mediaelement'));
    recData[id].medianame = document.getElementById(btn.getAttribute('medianame'));
    recData[id].mediapercent = btn.getAttribute('mediapercent');

    if (recStatus === 0) {
        audioTrack = new WebAudioTrack();
        audioTrackFile = new WebAudioTrack();

        audioTrack.startRecording(function() {

        });

        btn.className = "srecordingBTN button-xl-rec";
        btn.innerHTML = '<i class="fa fa-stop-circle"></i> Stop';

        recStatus = 1;
    } else {
        audioTrack.stopRecording(function() {

            /*
             * Upload mp3 to the server
             */

            btn.innerHTML = '<i class="fa fa-stop-circle"></i> Encoding...';
            btn.className = "srecordingBTN button-xl";
            btn.setAttribute("disabled", true);

            //$("#uploadingmsg").html("Uploading...");

            var data = new FormData();
            var action = "https://hokulele.us/speechtotext/requests.php?action=upload";
            data.append('file', audioTrack.getBlob(), 'audio.wav');
            $.ajax({
                type: 'POST',
                url: action,
                data: data,
                processData: false,
                contentType: false
            }).done(function (response) {
                console.log(response);

                btn.innerHTML = '<i class="fa fa-stop-circle"></i> Uploading...';
                //btn.innerHTML = '<i class="fa fa-microphone"></i> Start';
                //btn.className = "srecordingBTN button-xl";
                //btn.removeAttribute("disabled");

                //$("#uploadingmsg").html("");
                //$("#json").html(response.transcript);

                recData[id].ans.value = response.transcript;
                recData[id].ansDiv.innerHTML = response.transcript;

                $.post(typeRoot + "/ajax.php", { text: "--file uploading: " + btn.getAttribute('options') });

                var opts = JSON.parse(btn.getAttribute('options'));
                uploadFileGG(audioTrack.getBlob(), opts.repo_id, opts.itemid, opts.title, opts.ctx_id, btn);

                var grade = recData[id].grade;
                var medianame = recData[id].medianame;
                var mediapercent = recData[id].mediapercent;
                var mediaelement = recData[id].mediaelement;

                if(recData[id].targetanswerid === null){
                    var targetAnswer = "";
                } else {
                    var targetAnswer = recData[id].targetanswerid.value;
                }
                recData[id].grade.value = 'Updating...';

                $.post(typeRoot + "/ajax-score.php", {usageid: recData[id].usageid, targetAnswer: targetAnswer, slot: recData[id].slot, ans: recData[id].ans.value},
                    function (data) {
                        grade.value = JSON.parse(data).gradePercent;

                        if ((parseInt(grade.value) >= parseInt(mediapercent)) && mediapercent != 0) {
                            medianame.style.display = "block";
                            mediaelement.play();
                        } else {
                            console.log("No feedback: " + grade.value + " / " + mediapercent);
                        }
                    });
            }).fail(function (data) {
            });
        });

        recStatus = 0;

    }
}


function uploadFileGG(file, repo_id, itemid, title, ctx_id, btn) {
    var xhr = new XMLHttpRequest();
    var formdata = new FormData();
    formdata.append('repo_upload_file', file);
    formdata.append('sesskey', M.cfg.sesskey);
    formdata.append('repo_id', repo_id);
    formdata.append('itemid', itemid);
    formdata.append('title', title);
    formdata.append('overwrite', 1);
    formdata.append('ctx_id', ctx_id);
    var uploadUrl = M.cfg.wwwroot + "/repository/repository_ajax.php?action=upload";
    xhr.open("POST", uploadUrl, !0);
    xhr.btn = btn;
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
            var audioname = this.btn.getAttribute('audioname');
            document.getElementById(audioname).src = JSON.parse(xhr.responseText).url + '?' + Date.now();
            this.btn.innerHTML = '<i class="fa fa-microphone"></i> Start';
            this.btn.className = "srecordingBTN button-xl";
            this.btn.removeAttribute("disabled");
        }
    }
    xhr.upload.addEventListener("progress", function (evt) {
        if (evt.lengthComputable) {
            var percentComplete = evt.loaded / evt.total;
            percentComplete = parseInt(percentComplete * 100);
            xhr.btn.innerHTML = '<i class="fa fa-upload" style="background: linear-gradient(to top, white ' + percentComplete + '%, #a4c9e9 ' + percentComplete + '% 100%);' + '    -webkit-background-clip: text;' + '    -webkit-text-fill-color: transparent;' + '    display: initial;"></i>  Uploading... (' + percentComplete + '%)';
        }
    }, false);
    xhr.send(formdata);
}
