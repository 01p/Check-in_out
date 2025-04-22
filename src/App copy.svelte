<script>
    import { onMount, onDestroy } from 'svelte';
    import { readable, derived, get } from 'svelte/store';

    const BACKEND_URL = "http://localhost:8000/index.php"; // Update with your backend URL

    let isMenuVisible = false;
    function toggleMenu() {
        isMenuVisible = !isMenuVisible;
    }

    let questions = [];
    let selectedLanguage = "en";
    let randomQuestion;
    let gradientNumber = 1;
    let questionSet = "in"; 
    let showModal = false;
    let hash = "";
    let shareableLink = "";

    function generateHash() {
        return Math.random().toString(36).substring(2, 10);
    }

    function copyLink() {
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
        randomQuestion = getRandomQuestion();
    }

    function getRandomQuestion() {
        return questions[Math.floor(Math.random() * questions.length)];
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
        if (!hash || !randomQuestion) return;

        try {
            const response = await fetch(BACKEND_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    hash,
                    question: randomQuestion,
                    language: selectedLanguage,
                    gradient: gradientNumber,
                }),
            });

            const data = await response.json();
            if (!data.success) {
                console.error("Error updating backend:", data.error);
            }
        } catch (error) {
            console.error("Error updating backend:", error);
        }
    }

    async function loadQuestionFromBackend() {
        if (!hash) return;

        try {
            const response = await fetch(`${BACKEND_URL}?hash=${hash}`);
            const data = await response.json();

            if (data.error) {
                console.error("Error fetching question:", data.error);
                randomQuestion = "No question found for this session.";
            } else {
                randomQuestion = data.question;
                selectedLanguage = data.language;
                gradientNumber = data.gradient;
            }
        } catch (error) {
            console.error("Error loading question from backend:", error);
        }
    }

    function updateShareableLink() {
        shareableLink = `${window.location.origin}?hash=${hash}`;
        history.pushState({}, "", `?hash=${hash}`);
    }

    onMount(async () => {
        const params = new URLSearchParams(window.location.search);
        if (params.has("hash")) {
            hash = params.get("hash");
        } else {
            hash = generateHash();
        }
        if (params.has("lang")) selectedLanguage = params.get("lang");
        if (params.has("gradient")) gradientNumber = +params.get("gradient");
        await loadQuestions();
        if (params.has("hash")) {
            const response = await fetch(`${BACKEND_URL}?hash=${hash}`);
            const data = await response.json();
            if (data.question) {
                randomQuestion = data.question;
            }
        }
        updateShareableLink();
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
      <span
        class="close"
        role="button"
        tabindex="0"
        on:click={close}
        on:keydown={(e) => e.key === 'Enter' && close()}
      >
        &times;
      </span>
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