(() => {
  const root = document.querySelector('#skill-training');
  if (!root) return;

  const t = (key) => window.MyErpI18n?.t(key) || key;
  const selectors = {
    form: root.querySelector('[data-training-form]'),
    start: root.querySelector('[data-start-button]'),
    stop: root.querySelector('[data-stop-button]'),
    state: root.querySelector('[data-session-state]'),
    question: root.querySelector('[data-question-panel]'),
    feedback: root.querySelector('[data-feedback]'),
    status: root.querySelector('[data-status-message]'),
    answerForm: root.querySelector('[data-answer-form]'),
  };
  let session = JSON.parse(root.dataset.session || 'null');
  let attempt = JSON.parse(root.dataset.attempt || 'null');
  let questionTimer = 0;
  let tickTimer = 0;
  let heartbeatTimer = 0;
  let submitting = false;
  let isPageClosing = false;
  let audioContext = null;
  let soundEnabled = localStorage.getItem('my-erp-skill-sound') !== 'off';
  const languageSelector = root.querySelector('[data-training-language]');
  if (session?.training_language) {
    languageSelector.value = session.training_language;
  } else {
    languageSelector.value = document.documentElement.lang === 'vi' ? 'vi' : 'en';
  }

  const showStatus = (message, isError = false) => {
    selectors.status.textContent = message;
    selectors.status.classList.toggle('is-error', isError);
  };

  const request = async (url, values = {}) => {
    const body = new URLSearchParams({ csrf_token: root.dataset.csrf, ...values });
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', Accept: 'application/json' },
      body,
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || t('skill.requestError'));
    return data;
  };

  const setRunning = (running) => {
    selectors.start.hidden = running;
    selectors.stop.hidden = !running;
    selectors.state.textContent = running ? t('skill.sessionActive') : t('skill.notTraining');
    root.querySelectorAll('[data-training-form] input, [data-training-form] select').forEach((input) => {
      input.disabled = running;
    });
    root.querySelector('[data-session-indicator]').classList.toggle('is-active', running);
  };

  const prepareAudio = async () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return false;
    if (!audioContext) audioContext = new AudioContextClass();
    if (audioContext.state === 'suspended') await audioContext.resume();
    return audioContext.state === 'running';
  };

  const playAlert = async () => {
    if (!soundEnabled || !(await prepareAudio())) return false;
    const now = audioContext.currentTime;
    const notes = [1046, 784, 1046, 784, 1046, 784];
    notes.forEach((frequency, index) => {
      const start = now + index * 0.34 + Math.floor(index / 2) * 0.28;
      const oscillator = audioContext.createOscillator();
      const gain = audioContext.createGain();
      oscillator.type = 'triangle';
      oscillator.frequency.value = frequency;
      gain.gain.setValueAtTime(0.0001, start);
      gain.gain.exponentialRampToValueAtTime(0.65, start + 0.018);
      gain.gain.setValueAtTime(0.65, start + 0.16);
      gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.28);
      oscillator.connect(gain);
      gain.connect(audioContext.destination);
      oscillator.start(start);
      oscillator.stop(start + 0.29);
    });
    return true;
  };

  const renderAttempt = (value) => {
    attempt = value;
    selectors.question.hidden = !value;
    if (!value) return;
    selectors.question.innerHTML = `
      <div class="st-question-meta"><span class="st-type">${escapeHtml(t(value.question_type === 'theory' ? 'skill.typeTheory' : 'skill.typeExercise'))}</span><span class="st-countdown" data-countdown data-deadline="${Number(value.deadline_at) || 0}"></span></div>
      <h3 data-question-text>${escapeHtml(value.question)}</h3>
      <form data-answer-form>
        <input type="hidden" name="session_id" value="${Number(value.session_id) || Number(session?.id) || 0}">
        <input type="hidden" name="attempt_id" value="${Number(value.id) || 0}">
        <label class="st-field"><span>${escapeHtml(t('skill.yourAnswer'))}</span><textarea name="answer" rows="4" maxlength="10000" placeholder="${escapeHtml(t('skill.answerPlaceholder'))}" ${value.status === 'evaluation_failed' ? 'disabled' : ''}>${escapeHtml(value.answer_text || '')}</textarea></label>
        <div class="st-actions">
          <button class="st-button st-button-primary" type="submit" data-submit-answer ${value.status === 'evaluation_failed' ? 'hidden' : ''}>${escapeHtml(t('skill.submit'))}</button>
          <button class="st-button st-button-primary" type="button" data-retry-evaluation ${value.status === 'evaluation_failed' ? '' : 'hidden'}>${escapeHtml(t('skill.retry'))}</button>
          <span class="st-late-label" data-late-label ${value.is_late ? '' : 'hidden'}>${escapeHtml(t('skill.late'))}</span>
        </div>
      </form>`;
    selectors.answerForm = selectors.question.querySelector('[data-answer-form]');
    selectors.feedback.hidden = true;
    tick();
  };

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  })[character]);

  const renderFeedback = (value) => {
    selectors.feedback.innerHTML = `<div class="st-feedback-score"><strong>${Number(value.score)}/100</strong><span>${escapeHtml(value.verdict || '')}</span></div><p>${escapeHtml(value.explanation || '')}</p>`;
    selectors.feedback.hidden = false;
  };

  const stopTimers = () => {
    window.clearTimeout(questionTimer);
    window.clearInterval(tickTimer);
    window.clearInterval(heartbeatTimer);
    questionTimer = 0;
    tickTimer = 0;
    heartbeatTimer = 0;
  };

  const notify = async (title, body) => {
    let soundPlayed = false;
    try {
      soundPlayed = await playAlert();
    } catch (error) {
      soundPlayed = false;
    }
    const notificationState = root.querySelector('[data-notification-state]');
    if (!('Notification' in window)) {
      notificationState.textContent = t('skill.notificationsUnsupported');
    } else if (!window.isSecureContext) {
      notificationState.textContent = t('skill.notificationsNeedHttps');
    } else if (Notification.permission === 'granted') {
      try {
        const notice = new Notification(title, { body, icon: '/img/my-erp-favicon.png', tag: 'skill-training' });
        notice.onclick = () => { window.focus(); notice.close(); };
        notificationState.textContent = t('skill.notificationsGranted');
      } catch (error) {
        notificationState.textContent = t('skill.notificationsUnavailable');
      }
    } else {
      notificationState.textContent = t('skill.notificationsDenied');
    }
    selectors.question.classList.remove('st-question-arrived');
    void selectors.question.offsetWidth;
    selectors.question.classList.add('st-question-arrived');
    return soundPlayed;
  };

  const stopLocally = (message = t('skill.sessionEnded')) => {
    stopTimers();
    session = null;
    setRunning(false);
    renderAttempt(null);
    showStatus(message);
  };

  const scheduleQuestion = () => {
    if (!session || attempt) return;
    const dueAt = Number(session.next_question_at) * 1000;
    const delay = Math.max(0, dueAt - Date.now());
    window.clearTimeout(questionTimer);
    questionTimer = window.setTimeout(async () => {
      if (!session || attempt) return;
      showStatus(t('skill.generating'));
      try {
        const data = await request(root.dataset.questionUrl, { session_id: session.id });
        const dispatched = await request(root.dataset.dispatchUrl, {
          session_id: session.id,
          attempt_id: data.attempt.id,
        });
        attempt = dispatched.attempt;
        renderAttempt(attempt);
        await notify(t('skill.notificationTitle'), t('skill.notificationBody'));
        showStatus(t('skill.answerPrompt'));
      } catch (error) {
        showStatus(error.message, true);
        session.next_question_at = Math.floor(Date.now() / 1000) + 30;
        questionTimer = window.setTimeout(scheduleQuestion, 30000);
      }
    }, delay);
  };

  const tick = () => {
    const countdown = root.querySelector('[data-countdown]');
    if (!countdown || !attempt) return;
    const remaining = Number(countdown.dataset.deadline) - Math.floor(Date.now() / 1000);
    if (remaining > 0) {
      countdown.textContent = `${remaining}s`;
      return;
    }
    countdown.textContent = t('skill.timeUp');
    const answer = selectors.answerForm?.elements.answer;
    if (answer && answer.value.trim() !== '') {
      attempt.is_late = 1;
      root.querySelector('[data-late-label]').hidden = false;
      return;
    }
    if (!submitting && attempt.status === 'awaiting_answer') {
      submitting = true;
      request(root.dataset.timeoutUrl, { session_id: session.id, attempt_id: attempt.id })
        .then(() => stopLocally(t('skill.emptyTimeout')))
        .catch((error) => { showStatus(error.message, true); })
        .finally(() => { submitting = false; });
    }
  };

  const startHeartbeat = () => {
    window.clearInterval(heartbeatTimer);
    heartbeatTimer = window.setInterval(() => {
      if (session) request(root.dataset.heartbeatUrl, { session_id: session.id }).catch(() => {});
    }, 25000);
    tickTimer = window.setInterval(tick, 1000);
  };

  const onSubmission = async (event) => {
    event.preventDefault();
    if (!attempt || submitting) return;
    const answer = selectors.answerForm.elements.answer.value.trim();
    if (!answer) {
      showStatus(t('skill.answerRequired'), true);
      return;
    }
    submitting = true;
    const button = selectors.answerForm.querySelector('[data-submit-answer]');
    button.disabled = true;
    showStatus(t('skill.evaluating'));
    try {
      const data = await request(root.dataset.answerUrl, {
        session_id: session.id,
        attempt_id: attempt.id,
        answer,
      });
      renderFeedback(data.attempt);
      attempt = null;
      renderAttempt(null);
      session = data.next_session || session;
      showStatus(t('skill.answerSaved'));
      scheduleQuestion();
    } catch (error) {
      showStatus(error.message, true);
      if (/Retry the evaluation/i.test(error.message)) {
        attempt.answer_text = answer;
        attempt.status = 'evaluation_failed';
        renderAttempt(attempt);
      } else {
        button.disabled = false;
      }
    } finally {
      submitting = false;
    }
  };

  selectors.form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (session) return;
    const audioUnlock = prepareAudio();
    let permissionRequest = null;
    if ('Notification' in window && window.isSecureContext && Notification.permission === 'default') {
      permissionRequest = Notification.requestPermission().catch(() => 'denied');
    }
    await audioUnlock.catch(() => false);
    if (permissionRequest) await permissionRequest;
    const notificationState = root.querySelector('[data-notification-state]');
    if (!('Notification' in window)) {
      notificationState.textContent = t('skill.notificationsUnsupported');
    } else if (Notification.permission === 'granted') {
      notificationState.textContent = t('skill.notificationsGranted');
    } else if (!window.isSecureContext) {
      notificationState.textContent = t('skill.notificationsNeedHttps');
    } else {
      notificationState.textContent = t('skill.notificationsDenied');
    }
    const values = Object.fromEntries(new FormData(selectors.form).entries());
    showStatus(t('skill.starting'));
    try {
      const data = await request(root.dataset.startUrl, values);
      session = data.session;
      attempt = null;
      setRunning(true);
      showStatus(t('skill.sessionStarted'));
      startHeartbeat();
      scheduleQuestion();
    } catch (error) {
      showStatus(error.message, true);
    }
  });

  root.querySelector('[data-sound-enabled]').checked = soundEnabled;
  root.querySelector('[data-sound-enabled]').addEventListener('change', (event) => {
    soundEnabled = event.target.checked;
    localStorage.setItem('my-erp-skill-sound', soundEnabled ? 'on' : 'off');
  });
  root.querySelector('[data-test-sound]').addEventListener('click', async () => {
    const played = await playAlert();
    showStatus(played ? t('skill.soundTestPlayed') : t('skill.soundUnavailable'), !played);
  });

  selectors.stop.addEventListener('click', async () => {
    if (!session) return;
    const currentId = session.id;
    try {
      await request(root.dataset.stopUrl, { session_id: currentId });
      stopLocally(t('skill.sessionStopped'));
    } catch (error) {
      showStatus(error.message, true);
    }
  });

  root.addEventListener('submit', (event) => {
    if (event.target.matches('[data-answer-form]')) onSubmission(event);
  });

  root.addEventListener('click', async (event) => {
    if (!event.target.closest('[data-retry-evaluation]') || !attempt || submitting) return;
    submitting = true;
    showStatus(t('skill.evaluating'));
    try {
      const data = await request(root.dataset.retryUrl, { attempt_id: attempt.id });
      renderFeedback(data.attempt);
      attempt = null;
      renderAttempt(null);
      session = data.next_session || session;
      showStatus(t('skill.answerSaved'));
      scheduleQuestion();
    } catch (error) {
      showStatus(error.message, true);
    } finally {
      submitting = false;
    }
  });

  window.addEventListener('pagehide', () => {
    if (!session || isPageClosing) return;
    isPageClosing = true;
    stopTimers();
    const payload = new URLSearchParams({ csrf_token: root.dataset.csrf, session_id: String(session.id) });
    navigator.sendBeacon(root.dataset.stopUrl, new Blob([payload.toString()], {
      type: 'application/x-www-form-urlencoded;charset=UTF-8',
    }));
  });

  if (session) {
    setRunning(true);
    startHeartbeat();
    if (attempt) {
      if (!root.querySelector('[data-countdown]')) renderAttempt(attempt);
      tick();
    } else {
      scheduleQuestion();
    }
  }
})();
