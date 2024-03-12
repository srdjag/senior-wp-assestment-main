document.addEventListener("DOMContentLoaded", function () {
  const feedbackForm = document.getElementById("avArticleFeedbackForm");
  const yesButton = feedbackForm.querySelector(".yes-button");
  const noButton = feedbackForm.querySelector(".no-button");
  const articleID = article_voting_ajax.article_id;
  const nonce = article_voting_ajax.nonce;
  const ajaxUrl = article_voting_ajax.ajax_url;
  const yesResult = document.querySelector(".yes-result");
  const noResult = document.querySelector(".no-result");
  const qWrapper = document.querySelector(".inner-wrap--q");
  const aWrapper = document.querySelector(".inner-wrap--a");

  function handleButtonClick(type, event) {
    event.preventDefault();
    yesButton.setAttribute("disabled", true);
    noButton.setAttribute("disabled", true);

    jQuery.ajax({
      type: "POST",
      url: ajaxUrl,
      data: {
        action: "article_voting_process_feedback",
        feedback: type,
        post_id: articleID,
        nonce: nonce,
      },
      success: function (res) {
        if (res.status === "success" && qWrapper && aWrapper) {
          getVoteRatio().then(function () {
            qWrapper.style.display = "none";
            aWrapper.style.display = "flex";
            type
              ? yesResult.classList.add("voted")
              : noResult.classList.add("voted");
          });
        }
      },
      error: function (error) {},
    });
  }

  function getVoteRatio() {
    return new Promise(function (resolve, reject) {
      jQuery.ajax({
        type: "POST",
        url: ajaxUrl,
        data: {
          action: "article_voting_ratio",
          articleID: articleID,
          nonce: nonce,
        },
        success: function (res) {
          if (res.status === "success" && yesResult && noResult) {
            yesResult.innerHTML = res.ratio_true + "%";
            noResult.innerHTML = res.ratio_false + "%";
            resolve();
          }
        },
        error: function (error) {
          reject(error);
        },
      });
    });
  }

  function getVoteForCurrentUser() {
    jQuery.ajax({
      type: "POST",
      url: ajaxUrl,
      data: {
        action: "user_vote_status",
        post_id: articleID,
        nonce: nonce,
      },
      success: function (res) {
        if (res.status === "success" && qWrapper && aWrapper) {
          getVoteRatio().then(function () {
            qWrapper.style.display = "none";
            aWrapper.style.display = "flex";
            res.user_vote == "true"
              ? yesResult.classList.add("voted")
              : noResult.classList.add("voted");
          });
        }
      },
      error: function (error) {},
    });
  }

  if (yesButton) {
    yesButton.addEventListener("click", function (e) {
      handleButtonClick(true, e);
    });
  }

  if (noButton) {
    noButton.addEventListener("click", function (e) {
      handleButtonClick(false, e);
    });
  }

  getVoteForCurrentUser();
});
