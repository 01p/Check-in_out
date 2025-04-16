<!-- App.svelte: Main application file for managing questions, syncing state, and UI -->

<script>
    import { onMount, onDestroy } from 'svelte';

    const BACKEND_URL = "https://checkdesk.net/x/";

    let isMenuVisible = false;
    let questions = [];
    let selectedLanguage = "en";
    let randomQuestion;
    let gradientNumber = 1;
    let questionSet = "in";
    let showModal = false;
    let hash = "";
    let shareableLink = "";
    let pollingInterval;

    function toggleMenu() {
        isMenuVisible = !isMenuVisible;
    }

    function generateHash() {
        return Math.random().toString(36).substring(2, 10);
    }

    function copyLink() {
        navigator.clipboard.writeText(shareableLink).then(() => {
            console.log("Link copied to clipboard!");
        });
    }

    async function fetchBackendState() {
        if (!hash) return;

        try {
            const response = await fetch(`${BACKEND_URL}?hash=${hash}`);
            const data = await response.json();
            console.log("Fetched backend state:", data);

            if (data.question) {
                randomQuestion = data.question;
            }
        } catch (error) {
            console.error("Error fetching backend state:", error);
        }
    }

    async function updateBackend() {
        try {
            const response = await fetch(BACKEND_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ question: randomQuestion, hash }),
            });

            if (response.ok) {
                console.log("Backend updated successfully");
            } else {
                console.error("Failed to update backend");
            }
        } catch (error) {
            console.error("Error updating backend:", error);
        }
    }

    function updateShareableLink() {
        shareableLink = `${window.location.origin}?hash=${hash}`;
        history.pushState({}, "", `?hash=${hash}`);
    }

    function switchQuestion() {
        randomQuestion = getRandomQuestion();
        gradientNumber = (gradientNumber % 10) + 1;
        updateBackend();
    }

    async function toggleSet() {
        questionSet = questionSet === "in" ? "out" : "in";
        await loadQuestions();
    }

    async function loadQuestions() {
        const response = await fetch(`/questions_${questionSet}.json`);
        questions = await response.json();
        randomQuestion = getRandomQuestion();
    }

    function getRandomQuestion() {
        return questions[Math.floor(Math.random() * questions.length)];
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

    function close() {
        showModal = false;
    }

    onMount(async () => {
        console.log("App mounted");

        const params = new URLSearchParams(window.location.search);
        if (params.has("hash")) {
            hash = params.get("hash");
        } else {
            hash = generateHash();
        }

        await loadQuestions();

        if (params.has("hash")) {
            await fetchBackendState();
        }

        updateShareableLink();

        window.addEventListener('keydown', handleKeyDown);

        pollingInterval = setInterval(fetchBackendState, 6000);
    });

    onDestroy(() => {
        console.log("App destroyed");
        window.removeEventListener('keydown', handleKeyDown);
        clearInterval(pollingInterval);
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
</style>

<div class={`full gradient_${gradientNumber}`}>
  <div class="side-mobil" style="margin-left:10%;">
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
    <span class="bar" style="display: flex; flex-direction: column; align-items: center;">
      <button on:click={switchQuestion} class="button-mobile">
        S
      </button>
    </span>
    <p style="font-size:9px; margin:0px; writing-mode: vertical-rl; text-orientation: upright;">
      Double Tap
    </p>
    <span class="bar">
      <button on:click={toggleFullscreen} class="button-mobile">
        F
      </button>
    </span>
    <span class="bar">
      <button on:click={() => showModal = true} class="button-mobile">I</button>
    </span>
  </div>

  <div class="side desktop" style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 10px;">
    <span class="bar" style="margin-right: 10px;">
      <h1 style="color:#000000; margin-right: 10px;">
        {questionSet === "in" ? "Check-In" : "Check-Out"}
      </h1>
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
      <p class="question">{randomQuestion[selectedLanguage]}</p>
    </div>
  {:else}
    <p>Loading...</p>
  {/if}
</div>

{#if showModal}
  <div class="modal">
    <div class="modal-content">
      <span class="close" on:click={close}>&times;</span>
      <div style="font-size: 28px; margin-top:4vv; max-width: 70%;">
        <p>Hey there,</p>
        <p>
          It's the year of efficiency! Checking-in and checking-out with your team by answering a few questions together will help everyone work more productively and aligned to power through to the best results.
        </p>
        <h2>Share remotely</h2>
        <p>
          If you work remotely, just share the link (in Slack, Notion, etc.), and everyone will have the same question.
        </p>
        <h2>Share this link with your team to stay in sync:</h2>
        <div>
          <input
            type="text"
            readonly
            value={shareableLink}
            style="width: 100%; margin-bottom: 10px;"
          />
          <button on:click={copyLink}>Copy Link</button>
        </div>
        <p>
          When someone opens the link, they will see the same question as you.
        </p>
      </div>
    </div>
  </div>
{/if}