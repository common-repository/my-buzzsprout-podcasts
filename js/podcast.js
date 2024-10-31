function load_content() {
                var discripton = podcastlist[document.getElementById("podcast_embed").value]["Discription"];
                var transcript = podcastlist[document.getElementById("podcast_embed").value]["Transcript"];
                const discripton_content = tinyMCE.get("podcast_discription_text");
                const transcript_content = tinyMCE.get("podcast_transcript_text");
                if (null !== discripton_content && false === discripton_content.hidden) {
                    discripton_content.setContent(discripton);
                } else {
                    document.getElementById("podcast_discription_text").value = discripton;
                }
                if (transcript != null && transcript != "") {
                    var xhr = new XMLHttpRequest();
                    xhr.addEventListener("readystatechange", function () {
                        if (this.readyState === 4) {
                            if (null !== transcript_content && false === transcript_content.hidden) {
                                transcript_content.setContent(this.responseText);
                            } else {
                                document.getElementById("podcast_transcript_text").value = this.responseText;
                            }
                        }
                    });
                    xhr.open("GET", transcript);
                    xhr.send();
                }
            }
function switchTab(tab) {
        if (tab === 'tab-1') {
            document.getElementById("podcast-tab-block-1").style.display = "block";
            document.getElementById("podcast-tab-block-2").style.display = "none";
        } else {
             document.getElementById("podcast-tab-block-1").style.display = "none";
             document.getElementById("podcast-tab-block-2").style.display = "block";
        }
    }