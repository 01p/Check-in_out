<script>
  import { onMount, onDestroy } from 'svelte';
  import { readable, derived, get } from 'svelte/store';

  // Dynamically set the backend URL based on the frontend's origin
  const BACKEND_URL = `${window.location.origin}/backend/index.php`;

  let isMenuVisible = false;
  let questions = [];
  let selectedLanguage = "en";
  let randomQuestion;
  let gradientNumber = 1;
  let questionSet = "in"; 
  let showModal = false;
  let hash = "";
  let shareableLink = "";
  let mode = "normal"; // Modes: "normal", "presenter", "listener"
  let pollingInterval;
  let lastQuestion = null; // Stores the last question
  let lastUpdateTime = Date.now(); // Stores the timestamp of the last update

  function generateHash() {
      return Math.random().toString(36).substring(2, 10);
  }

  function copyLink() {
      if (!hash) {
          hash = generateHash(); // Generate hash only when copying the link
          mode = "presenter"; // Switch to presenter mode
          updateShareableLink();
      }
      navigator.clipboard.writeText(shareableLink).then(() => {
          alert("Link copied to clipboard!");
      });
  }

  const urlParams = readable(new URLSearchParams(window.location.search), set => {
      function updateParams() {
          set(new URLSearchParams(window.location.search));
      }
      window.addEventListener('popstate', updateParams);
      return () => window.removeEventListener('popstate', updateParams);
  });

  const currentQuestionId = derived(urlParams, $urlParams => $urlParams.get('id') || null);

  function close() {
      showModal = false;
  }

  let lastTouchTime = 0;
  function handleDoubleClickTap() {
      const now = new Date().getTime();
      const timesince = now - lastTouchTime;
      if ((timesince < 300) && (timesince > 0)) {
          switchQuestion();
      }
      lastTouchTime = now;
  }

  onMount(async () => {
      window.addEventListener('keydown', handleKeyDown);
      window.addEventListener('touchend', handleDoubleClickTap);
      await loadQuestions();
  });

  onDestroy(() => {
      window.removeEventListener('keydown', handleKeyDown);
      window.removeEventListener('touchend', handleDoubleClickTap);
  });

  async function loadQuestions() {
      const response = await fetch(`/questions_${questionSet}.json`);
      questions = await response.json();
      randomQuestion = getRandomQuestion(); // Ensure randomQuestion is a string
  }

  function getRandomQuestion() {
      async function loadQuestions() {
        const response = await fetch(`/questions_${questionSet}.json`);
        questions = await response.json();
        randomQuestion = getRandomQuestion(); // Ensure randomQuestion is a string
      }      const question = questions[Math.floor(Math.random() * questions.length)];
      // If the question is an object, return the question for the selected language
      return typeof question === "string" ? question : question[selectedLanguage] || "No question available.";
  }

  function switchQuestion() {
      if (mode === "listener") {
          console.log("Shuffle disabled in Listener Mode");
          return; // Disable shuffle in listener mode
      }

      randomQuestion = getRandomQuestion();
      gradientNumber = (gradientNumber % 10) + 1;

      if (mode === "presenter") {
          console.log("Updating backend in Presenter Mode");
          updateBackend(); // Only update the backend in presenter mode
      }
  }

  async function toggleSet() {
      questionSet = questionSet === "in" ? "out" : "in";
      await loadQuestions();
  }

  function handleKeyDown(event) {
      if (event.key === ' ') {
          switchQuestion();
      }
  }

  function toggleFullscreen() {
      let element = document.documentElement;
      if (!document.fullscreenElement) {
          element.requestFullscreen();
      } else {
          document.exitFullscreen();
      }
  }

  async function updateBackend() {
      if (!hash || !randomQuestion) {
          // console.log("Skipping updateBackend: Missing hash or randomQuestion");
          return;
      }

      const payload = {
          hash,
          question: typeof randomQuestion === "string" ? randomQuestion : randomQuestion[selectedLanguage] || "",
          language: selectedLanguage,
          gradient: gradientNumber,
      };

      // console.log("Sending payload to backend:", payload); // Debugging: Log the payload

      try {
          const uniqueParam = `?_ts=${Date.now()}`; // Add a timestamp to prevent caching
          const response = await fetch(`${BACKEND_URL}${uniqueParam}`, {
              method: "POST",
              headers: {
                  "Content-Type": "application/json",
              },
              body: JSON.stringify(payload),
          });

          const data = await response.json();
          // console.log("Parsed backend response:", JSON.stringify(data, null, 2)); // Log the full response

          if (!data.success) {
              // console.error("Error updating backend:", data.error);
          }
      } catch (error) {
          // console.error("Error updating backend:", error);
      }
  }

  async function loadQuestionFromBackend() {
      if (!hash) {
          // console.log("Skipping loadQuestionFromBackend: Missing hash");
          return;
      }

      try {
          const uniqueParam = `&_ts=${Date.now()}`; // Add a timestamp to prevent caching
          // console.log(`Fetching question from backend with hash: ${hash}`);
          const response = await fetch(`${BACKEND_URL}?hash=${hash}${uniqueParam}`);
          const data = await response.json();
          // console.log("Parsed backend response:", data); // Debugging: Log the parsed response

          if (data.error) {
              // console.error("Error fetching question:", data.error);
              randomQuestion = "No question found for this session.";
          } else {
              randomQuestion = typeof data.question === "string" ? data.question : "Invalid question format.";
              selectedLanguage = data.language;
              gradientNumber = data.gradient;
              // console.log("Updated frontend state with backend data:", {
              //   randomQuestion,
              //   selectedLanguage,
              //   gradientNumber,
              // });
          }
      } catch (error) {
          // console.error("Error loading question from backend:", error);
      }
  }

  function updateShareableLink() {
    if (mode === "presenter") {
      shareableLink = `${window.location.origin}?${hash}`; // Add only the hash to the URL
      history.pushState({}, "", `?${hash}`); // Update the browser URL
      console.log(`Updated URL with hash: ${hash}`);
    }
  }

  function openInfoModal() {
      if (!hash && mode === "normal") {
          hash = generateHash(); // Generate hash when opening the modal in normal mode
          updateShareableLink();
          mode = "presenter"; // Switch to presenter mode
      }
      showModal = true;
  }

  function startPolling() {
    if (mode !== "listener") {
        // console.log("Polling is only active in Listener Mode. Skipping...");
        return;
    }

    if (pollingInterval) {
        // console.log("Polling is already active.");
        return;
    }

    // console.log("Starting polling...");
    pollingInterval = setInterval(async () => {
        // console.log("Polling backend for updates...");
        const previousQuestion = randomQuestion; // Store the current question
        await loadQuestionFromBackend();

        // Check if the question has changed
        if (randomQuestion !== previousQuestion) {
            // console.log("New question detected:", randomQuestion);
        } else {
            // console.log("No new question detected.");
        }
    }, 3000); // Poll every 3 seconds

    // Stop polling after 5 minutes (300,000ms)
    setTimeout(() => {
        // console.log("Polling timeout reached. Stopping polling.");
        stopPolling();
    }, 600000); // 10 minutes
}

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
      // console.log("Polling stopped.");
    }
  }

  function leaveListenerMode() {
    stopPolling(); // Stop the polling interval
    mode = "normal"; // Switch to Normal Mode
    hash = ""; // Clear the hash
    // console.log("Switched to Normal Mode");
    loadQuestions(); // Load questions for Normal Mode
    showModal = false; // Close the modal
  }

  onMount(async () => {
      const query = window.location.search.slice(1); // Remove the "?" from the query string
      // console.log("Raw Query String:", query); // Debugging: Log the raw query string

      if (query && !query.includes("=")) {
        // If the query string exists and does not contain "=", treat it as the hash
        hash = query;
        mode = "listener"; // Enter listener mode
        // console.log(`Detected hash in URL: ${hash}, entering Listener Mode`);
        await loadQuestionFromBackend();

        // Start polling every 3 seconds in Listener Mode
        startPolling();
      } else {
        // Default behavior if no valid hash is found
        mode = "normal";
        // console.log("No hash detected, entering Normal Mode");
        await loadQuestions();
      }

      // Only update the shareable link if in Presenter Mode
      if (mode === "presenter") {
        updateShareableLink();
      }
  });

  onDestroy(() => {
    // Clear the polling interval when the component is destroyed
    stopPolling();
  });
</script>

<style>
.side-mobil {
  display: none;
}

@media (min-width: 768px) {
  .side-mobil {
    display: none;
  }
  .side.desktop {
    display: flex;
  }
}

@media (max-width: 767px) {
  .side.desktop {
    display: none;
  }
  .side-mobil {
    display: flex;
  }
}

.disabled {
  opacity: 0.5;
  pointer-events: none;
}
</style>

<div class={`full gradient_${gradientNumber}`}>
<div class="side-mobil" style="margin-left:10%;">
{#if mode !== "listener"}
  <span class="bar">
    <button on:click={toggleSet} class="button-mobile">
      {questionSet === "in" ? "O" : "I"}
    </button>
  </span>
  <span class="bar">
    <select bind:value={selectedLanguage} class="dropdown button-mobile">
      <option value="en">EN</option>
      <option value="de">DE</option>
      <option value="it">IT</option>
      <option value="fr">FR</option>
    </select>
  </span>
  <span class="bar">
    <button on:click={switchQuestion} class="button-mobile">
      S
    </button>
  </span>
{/if}
  <p style="font-size:9px; margin:0px; writing-mode: vertical-rl; text-orientation: upright;" class="fade-out-animation">
    Double Tap
  </p>
  <span class="bar">
    <button on:click={toggleFullscreen} class="button-mobile">
      F
    </button>
  </span>
  <span class="bar">
    <button on:click={openInfoModal} class="button-mobile">I</button>
  </span>
</div>

<div class="side desktop">
  {#if mode !== "listener"}
    <span class="bar" style="margin-right: 10px;">
      <button on:click={toggleSet} class="button">
        {questionSet === "in" ? "Check-Out" : "Check-In"}
      </button>
    </span>
    <span class="bar" style="margin-right: 10px;">
      <select id="language-select" bind:value={selectedLanguage} class="dropdown">
        <option value="en">English</option>
        <option value="de">German</option>
        <option value="it">Italian</option>
        <option value="fr">French</option>
      </select>
    </span>
    <span class="bar" style="margin-right: 10px;">
      <button on:click={switchQuestion} class="button">
        Shuffle Question
      </button>
      <p style="font-size: 7px; margin: 5px 0;" class="fade-out-animation">(Press Space to Shuffle)</p>
    </span>
  {/if}
  <span class="bar" style="margin-right: 10px;">
    <button on:click={toggleFullscreen} class="button">
      Toggle Fullscreen
    </button>
  </span>
  <span class="bar">
    <button on:click={() => showModal = true} class="button">
      Info
    </button>
  </span>
</div>

{#if randomQuestion}
  <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
    <p class="question">{randomQuestion}</p>
  </div>
{:else}
  <p>Loading...</p>
{/if}
</div>

{#if showModal}
<div class="modal">
  <div class="modal-content">
    <span
      class="close"
      role="button"
      tabindex="0"
      on:click={close}
      on:keydown={(e) => e.key === 'Enter' && close()}
    >
      &times;
    </span>
    <div style="font-size: 28px; margin-top: 4vv; max-width: 70%;">
      <p>Hey there,</p>
      {#if mode === "normal"}
        <p>You are in <strong>Normal Mode</strong>. Click "Copy Link" to enter Presenter Mode.</p>
      {:else if mode === "presenter"}
        <p>You are in <strong>Presenter Mode</strong>. Share the link with your team.</p>
      {:else if mode === "listener"}
        <p>You are in <strong>Listener Mode</strong>. You are viewing the presenter’s question. Shuffle is disabled.</p>
        <button on:click={leaveListenerMode} class="button">
          Leave Listener Mode
        </button>
      {/if}

      <h2>Share this link with your team to stay in sync:</h2>
      <div>
        <input
          type="text"
          readonly
          value={shareableLink}
          style="width: 100%; margin-bottom: 10px;"
        />
        <button on:click={copyLink} class="button-copy-link">
          Copy Link
        </button>
      </div>

      <p>
        When someone opens the link, they will see the same question as you.
      </p>

      <p style="margin-top: 20px; font-size: 18px; line-height: 1.5;">
        Our new check-in app invites you to pause, reflect, and connect with others through heartfelt questions. Share what moves you, discover what binds us, and spark conversations that matter. Choose prompts that feel right for the moment or create your own. Begin your journey of deeper connection today.
      </p>
    </div>
  </div>
</div>
{/if}