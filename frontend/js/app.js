document.addEventListener("DOMContentLoaded", () => {
  // Basic routing and UI toggling
  const authView = document.getElementById("authView");
  const dashboardView = document.getElementById("dashboardView");
  const mainNavbar = document.getElementById("mainNavbar");

  const rootUrl = "../backend/api";

  // Auth Sections
  const loginSection = document.getElementById("loginSection");
  const registerSection = document.getElementById("registerSection");
  const resetSection = document.getElementById("resetSection");

  // Forms
  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const resetRequestForm = document.getElementById("resetRequestForm");
  const resetConfirmForm = document.getElementById("resetConfirmForm");

  // Show functions
  const showLogin = () => {
    loginSection.classList.remove("d-none");
    registerSection.classList.add("d-none");
    resetSection.classList.add("d-none");
  };

  const showRegister = () => {
    loginSection.classList.add("d-none");
    registerSection.classList.remove("d-none");
    resetSection.classList.add("d-none");
  };

  const showReset = () => {
    loginSection.classList.add("d-none");
    registerSection.classList.add("d-none");
    resetSection.classList.remove("d-none");
  };

  document.getElementById("showRegisterBtn")?.addEventListener("click", (e) => {
    e.preventDefault();
    showRegister();
  });
  document.getElementById("showResetBtn")?.addEventListener("click", (e) => {
    e.preventDefault();
    showReset();
  });
  document
    .getElementById("showLoginFromRegisterBtn")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showLogin();
    });
  document
    .getElementById("showLoginFromResetBtn")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showLogin();
    });

  // Helpers
  const showAlert = (section, msg, type) => {
    const alertEl = document.getElementById(section + "Alert");
    if (alertEl) {
      alertEl.className = `alert alert-${type}`;
      alertEl.innerText = msg;
      alertEl.classList.remove("d-none");
      setTimeout(() => alertEl.classList.add("d-none"), 3000);
    }
  };

  // Toggle navigation based on JWT presence
  const checkAuth = () => {
    const token = localStorage.getItem("token");
    if (token) {
      authView.style.display = "none";
      dashboardView.style.display = "block";
      mainNavbar.style.display = "flex";
      // Tự động load dữ liệu khi đã login
      if (typeof fetchNotes === "function") fetchNotes();
      if (typeof fetchLabels === "function") fetchLabels();
    } else {
      authView.style.display = "flex";
      dashboardView.style.display = "none";
      mainNavbar.style.display = "none";
      showLogin();
    }
  };

  // Login Submission
  loginForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = document.getElementById("loginEmail").value;
    const password = document.getElementById("loginPassword").value;

    try {
      const res = await fetch(`${rootUrl}/auth/login.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });
      const data = await res.json();
      if (res.ok) {
        localStorage.setItem("token", data.token);
        localStorage.setItem("user", JSON.stringify(data.user));
        if (data.user.theme_preference) {
          document.documentElement.setAttribute(
            "data-bs-theme",
            data.user.theme_preference,
          );
          localStorage.setItem("theme", data.user.theme_preference);
        }
        checkAuth();
      } else {
        showAlert("login", data.error || "Login failed", "danger");
      }
    } catch (err) {
      showAlert("login", "Server connection error", "danger");
    }
  });

  // Register Submission
  registerForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = document.getElementById("registerEmail").value;
    const password = document.getElementById("registerPassword").value;
    const display_name = document.getElementById("registerName").value;

    try {
      const res = await fetch(`${rootUrl}/auth/register.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password, display_name }),
      });
      const data = await res.json();
      if (res.ok) {
        showAlert(
          "register",
          "Registration successful! You can now log in.",
          "success",
        );
        setTimeout(showLogin, 2000);
      } else {
        showAlert("register", data.error || "Registration failed", "danger");
      }
    } catch (err) {
      showAlert("register", "Server connection error", "danger");
    }
  });

  // Request Reset OTP
  resetRequestForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = document.getElementById("resetEmail").value;
    try {
      const res = await fetch(`${rootUrl}/auth/reset_password.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "request", email }),
      });
      const data = await res.json();
      if (res.ok) {
        showAlert(
          "reset",
          "OTP sent to email (simulated: " + data.otp + ")",
          "success",
        );
        resetRequestForm.classList.add("d-none");
        resetConfirmForm.classList.remove("d-none");
      } else {
        showAlert("reset", data.error || "Request failed", "danger");
      }
    } catch (err) {
      showAlert("reset", "Server connection error", "danger");
    }
  });

  // Confirm Reset
  resetConfirmForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const otp = document.getElementById("resetOTP").value;
    const new_password = document.getElementById("resetNewPassword").value;
    try {
      const res = await fetch(`${rootUrl}/auth/reset_password.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "reset", otp, new_password }),
      });
      const data = await res.json();
      if (res.ok) {
        showAlert("reset", "Password reset successfully!", "success");
        setTimeout(showLogin, 2000);
      } else {
        showAlert("reset", data.error || "Reset failed", "danger");
      }
    } catch (err) {
      showAlert("reset", "Server connection error", "danger");
    }
  });

  // Initialize Theme
  const toggleTheme = async () => {
    const currentTheme = document.documentElement.getAttribute("data-bs-theme");
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-bs-theme", newTheme);
    localStorage.setItem("theme", newTheme);

    if (localStorage.getItem("token")) {
      await fetch(`${rootUrl}/auth/preferences.php`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: "Bearer " + localStorage.getItem("token"),
        },
        body: JSON.stringify({ theme_preference: newTheme }),
      });
    }
  };

  if (localStorage.getItem("theme") === "dark") {
    document.documentElement.setAttribute("data-bs-theme", "dark");
  }

  document.getElementById("themeToggle")?.addEventListener("click", (e) => {
    e.preventDefault();
    toggleTheme();
  });

  document.getElementById("logoutBtn")?.addEventListener("click", (e) => {
    e.preventDefault();
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    checkAuth();
  });

  // -------------------------------------------------------------
  // Core Note Application Logic
  // -------------------------------------------------------------

  let notes = [];
  let labels = [];
  let filteredNotes = [];
  let isGridView = true;
  let currentNoteId = null;

  let searchTimeout = null;
  let autoSaveTimeout = null;

  const notesContainer = document.getElementById("notesContainer");
  const emptyNotesMsg = document.getElementById("emptyNotesMsg");
  const labelsList = document.getElementById("labelsList");
  const searchInput = document.getElementById("searchInput");

  const editorModal = new bootstrap.Modal(
    document.getElementById("noteEditorModal"),
  );
  const editorTitle = document.getElementById("editorTitle");
  const editorContent = document.getElementById("editorContent");
  const saveStatus = document.getElementById("saveStatus");

  const editorImgInput = document.getElementById("editorImgInput");
  const editorAttachments = document.getElementById("editorAttachments");

  const escapeHTML = (str) => {
    if (!str) return "";
    return String(str).replace(
      /[&<>'"]/g,
      (tag) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          "'": "&#39;",
          '"': "&quot;",
        })[tag] || tag,
    );
  };

  const getApiHeaders = () => {
    return {
      "Content-Type": "application/json",
      Authorization: "Bearer " + localStorage.getItem("token"),
    };
  };

  // Hàm Render File Đính kèm (Đưa lên trên để gọi không bị lỗi)
  function renderAttachments(note) {
    if (!editorAttachments) return;
    editorAttachments.innerHTML = "";
    if (!note || !note.attachments) return;
    note.attachments.forEach((att) => {
      const imgUrl = `../backend/${att.file_path}`;
      const div = document.createElement("div");
      div.className = "position-relative d-inline-block";
      div.innerHTML = `
                <img src="${imgUrl}" alt="${att.original_name}" class="img-thumbnail rounded shadow-sm" style="max-height: 100px;">
            `;
      editorAttachments.appendChild(div);
    });
  }

  // Load Notes
  const fetchNotes = async () => {
    try {
      const res = await fetch(`${rootUrl}/notes/read.php`, {
        headers: getApiHeaders(),
      });
      if (res.ok) {
        notes = await res.json();
        renderNotes();
      } else if (res.status === 401) {
        localStorage.removeItem("token");
        checkAuth();
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Load Labels
  const fetchLabels = async () => {
    try {
      const res = await fetch(`${rootUrl}/labels/read.php`, {
        headers: getApiHeaders(),
      });
      if (res.ok) {
        labels = await res.json();
        renderLabels();
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Render Logic đã gộp mảng truyền vào
  const renderNotes = (query = "", arr = null) => {
    const sourceData = arr || notes;
    notesContainer.innerHTML = "";

    filteredNotes = sourceData.filter((n) => {
      const searchText = query.toLowerCase();
      return (
        n.title?.toLowerCase().includes(searchText) ||
        n.content?.toLowerCase().includes(searchText)
      );
    });

    if (filteredNotes.length === 0) {
      notesContainer.appendChild(emptyNotesMsg);
      emptyNotesMsg.style.display = "block";
    } else {
      emptyNotesMsg.style.display = "none";
      notesContainer.className = isGridView
        ? "row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4"
        : "row row-cols-1 g-3 notes-list-view";

      filteredNotes.forEach((note) => {
        const col = document.createElement("div");
        col.className = "col";
        col.innerHTML = `
                    <div class="note-card card h-100 rounded-4" data-id="${note.id}">
                        <div class="card-body">
                            ${note.is_pinned == 1 ? '<i class="bi bi-pin-fill float-end text-primary ms-2"></i>' : ""}
                            ${note.is_locked == 1 ? '<i class="bi bi-lock-fill float-end text-warning ms-2"></i>' : ""}
                            ${note.access_level ? '<i class="bi bi-people-fill float-end text-info ms-2" title="Shared with you"></i>' : ""}
                            <h5 class="note-card-title">${escapeHTML(note.title) || "Untitled Note"}</h5>
                            <p class="note-card-content mt-2 mb-0">${escapeHTML(note.content) || ""}</p>
                            ${note.owner_email ? `<small class="text-muted d-block mt-2"><i class="bi bi-person"></i> ${escapeHTML(note.owner_name)}</small>` : ""}
                        </div>
                    </div>
                `;
        col.querySelector(".note-card").addEventListener("click", () => {
          // Prevent editing if read-only
          if (note.access_level === "read") {
            editorTitle.setAttribute("readonly", "true");
            editorContent.setAttribute("readonly", "true");
            document.getElementById("editorActions")?.classList.add("d-none");
          } else {
            editorTitle.removeAttribute("readonly");
            editorContent.removeAttribute("readonly");
            document
              .getElementById("editorActions")
              ?.classList.remove("d-none");
          }
          openEditor(note);
        });
        notesContainer.appendChild(col);
      });
    }
  };

  const renderLabels = () => {
    labelsList.innerHTML = "";
    labels.forEach((label) => {
      const li = document.createElement("li");
      li.className = "nav-item";
      li.innerHTML = `<a href="#" class="nav-link label-link" data-id="${label.id}">
                <i class="bi bi-tag-fill me-2" style="color: ${label.color}"></i> ${label.name}
            </a>`;
      labelsList.appendChild(li);
    });
  };

  const unlockModal = new bootstrap.Modal(
    document.getElementById("unlockModal"),
  );
  const unlockPasswordInput = document.getElementById("unlockPassword");
  const unlockAlert = document.getElementById("unlockAlert");
  let pendingUnlockNote = null;

  // Đã gộp logic gọi attachments vào
  const openEditor = (note = null) => {
    clearTimeout(autoSaveTimeout);
    if (note) {
      currentNoteId = note.id;

      if (note.is_locked == 1 && note.content === "This note is locked.") {
        pendingUnlockNote = note;
        unlockPasswordInput.value = "";
        unlockAlert.classList.add("d-none");
        unlockModal.show();
        return;
      }

      editorTitle.value = note.title;
      editorContent.value = note.content;
      saveStatus.innerText = "Saved";
      renderAttachments(note);
    } else {
      currentNoteId = null;
      editorTitle.value = "";
      editorContent.value = "";
      saveStatus.innerText = "";
      renderAttachments(null);
    }
    editorModal.show();
  };

  document
    .getElementById("createNoteBtn")
    ?.addEventListener("click", () => openEditor());

  // Editor Auto-Save Logic
  const handleAutoSave = () => {
    saveStatus.innerText = "Saving...";
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(async () => {
      const payload = {
        title: editorTitle.value,
        content: editorContent.value,
      };

      if (currentNoteId) {
        payload.id = currentNoteId;
        await fetch(`${rootUrl}/notes/update.php`, {
          method: "PUT",
          headers: getApiHeaders(),
          body: JSON.stringify(payload),
        });

        const n = notes.find((x) => x.id === currentNoteId);
        if (n) {
          n.title = payload.title;
          n.content = payload.content;
        }
      } else {
        const res = await fetch(`${rootUrl}/notes/create.php`, {
          method: "POST",
          headers: getApiHeaders(),
          body: JSON.stringify(payload),
        });
        if (res.ok) {
          const data = await res.json();
          currentNoteId = data.note.id;
          notes.unshift(data.note);
        }
      }
      saveStatus.innerText = "Saved";
      renderNotes(searchInput.value);
    }, 1000);
  };

  editorTitle?.addEventListener("input", handleAutoSave);
  editorContent?.addEventListener("input", handleAutoSave);

  // Unlock Submit
  document
    .getElementById("unlockSubmitBtn")
    ?.addEventListener("click", async () => {
      if (!pendingUnlockNote) return;
      const password = unlockPasswordInput.value;
      const btn = document.getElementById("unlockSubmitBtn");
      btn.innerText = "Unlocking...";
      try {
        const res = await fetch(`${rootUrl}/notes/unlock.php`, {
          method: "POST",
          headers: getApiHeaders(),
          body: JSON.stringify({ id: pendingUnlockNote.id, password }),
        });
        const data = await res.json();
        if (res.ok) {
          pendingUnlockNote.content = data.content;
          pendingUnlockNote.attachments = data.attachments;
          unlockModal.hide();
          openEditor(pendingUnlockNote);
        } else {
          unlockAlert.innerText = data.error || "Incorrect Password";
          unlockAlert.classList.remove("d-none");
        }
      } catch (err) {
        unlockAlert.innerText = "Connection error";
        unlockAlert.classList.remove("d-none");
      }
      btn.innerText = "Unlock";
    });

  // Lock Note Logic
  document
    .getElementById("editorLockBtn")
    ?.addEventListener("click", async () => {
      if (!currentNoteId) return;
      const password = prompt(
        "Enter a password to lock this note (or leave blank to remove lock):",
      );
      if (password !== null) {
        const res = await fetch(`${rootUrl}/notes/lock.php`, {
          method: "POST",
          headers: getApiHeaders(),
          body: JSON.stringify({ id: currentNoteId, password }),
        });
        if (res.ok) {
          const n = notes.find((x) => x.id === currentNoteId);
          if (n) n.is_locked = password === "" ? 0 : 1;
          alert(
            password === "" ? "Lock removed!" : "Note locked successfully!",
          );
          renderNotes(searchInput.value);
        }
      }
    });

  // Layout Toggle
  document
    .getElementById("viewGridBtn")
    ?.addEventListener("click", function () {
      isGridView = true;
      this.classList.add("active");
      document.getElementById("viewListBtn").classList.remove("active");
      renderNotes(searchInput.value);
    });

  document
    .getElementById("viewListBtn")
    ?.addEventListener("click", function () {
      isGridView = false;
      this.classList.add("active");
      document.getElementById("viewGridBtn").classList.remove("active");
      renderNotes(searchInput.value);
    });

  // Search Debounce
  searchInput?.addEventListener("input", (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      renderNotes(e.target.value);
    }, 300);
  });

  // Note Actions in Editor
  document
    .getElementById("editorDeleteBtn")
    ?.addEventListener("click", async () => {
      if (!currentNoteId) return;
      if (confirm("Are you sure you want to delete this note?")) {
        await fetch(`${rootUrl}/notes/delete.php?id=${currentNoteId}`, {
          method: "DELETE",
          headers: getApiHeaders(),
        });
        notes = notes.filter((n) => n.id !== currentNoteId);
        editorModal.hide();
        renderNotes(searchInput.value);
      }
    });

  document
    .getElementById("editorPinBtn")
    ?.addEventListener("click", async () => {
      if (!currentNoteId) return;
      const n = notes.find((x) => x.id === currentNoteId);
      if (n) {
        const newPinStatus = n.is_pinned == 1 ? 0 : 1;
        await fetch(`${rootUrl}/notes/update.php`, {
          method: "PUT",
          headers: getApiHeaders(),
          body: JSON.stringify({ id: currentNoteId, is_pinned: newPinStatus }),
        });
        n.is_pinned = newPinStatus;
        renderNotes(searchInput.value);
      }
    });

  // File Image Upload Logic
  if (editorImgInput) {
    editorImgInput.addEventListener("change", async (e) => {
      if (!currentNoteId) {
        alert(
          "Please type something to save the note first before attaching images.",
        );
        return;
      }
      const file = e.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("note_id", currentNoteId);
      formData.append("image", file);

      try {
        saveStatus.innerText = "Uploading...";
        const res = await fetch(`${rootUrl}/notes/upload.php`, {
          method: "POST",
          headers: { Authorization: "Bearer " + localStorage.getItem("token") },
          body: formData,
        });
        const data = await res.json();
        if (res.ok) {
          const n = notes.find((x) => x.id === currentNoteId);
          if (n) {
            if (!n.attachments) n.attachments = [];
            n.attachments.push(data.attachment);
            renderAttachments(n);
          }
          saveStatus.innerText = "Saved";
        } else {
          alert(data.error || "Failed to upload image");
          saveStatus.innerText = "Error";
        }
      } catch (err) {
        console.error(err);
        saveStatus.innerText = "Error";
      }
      editorImgInput.value = "";
    });
  }

  // Share Note Logic
  const shareModal = new bootstrap.Modal(document.getElementById("shareModal"));
  const shareEmail = document.getElementById("shareEmail");
  const shareAccess = document.getElementById("shareAccess");
  const shareAlert = document.getElementById("shareAlert");
  const sharedList = document.getElementById("sharedList");

  const fetchShares = async (id) => {
    if (!sharedList) return;
    sharedList.innerHTML = `<li class="list-group-item text-center">Loading...</li>`;
    try {
      const res = await fetch(`${rootUrl}/shares/list.php?note_id=${id}`, {
        headers: getApiHeaders(),
      });
      if (res.ok) {
        const data = await res.json();
        sharedList.innerHTML = "";
        if (data.length === 0) {
          sharedList.innerHTML = `<li class="list-group-item text-muted text-center">Not shared.</li>`;
        } else {
          data.forEach((s) => {
            sharedList.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${s.shared_with_email}</span>
                                <span class="badge bg-secondary rounded-pill">${s.access_level}</span>
                            </li>
                        `;
          });
        }
      } else {
        sharedList.innerHTML = `<li class="list-group-item text-danger text-center">Permission Denied.</li>`;
      }
    } catch (err) {
      sharedList.innerHTML = `<li class="list-group-item text-danger text-center">Network Error.</li>`;
    }
  };

  document.getElementById("editorShareBtn")?.addEventListener("click", () => {
    if (!currentNoteId) {
      alert("Save the note first before sharing.");
      return;
    }
    if (shareEmail) shareEmail.value = "";
    if (shareAlert) shareAlert.classList.add("d-none");
    fetchShares(currentNoteId);
    shareModal.show();
  });

  document
    .getElementById("shareSubmitBtn")
    ?.addEventListener("click", async () => {
      if (!shareEmail.value) return;
      const btn = document.getElementById("shareSubmitBtn");
      btn.innerText = "Sharing...";
      try {
        const res = await fetch(`${rootUrl}/shares/create.php`, {
          method: "POST",
          headers: getApiHeaders(),
          body: JSON.stringify({
            note_id: currentNoteId,
            email: shareEmail.value,
            access_level: shareAccess.value,
          }),
        });
        const data = await res.json();
        if (res.ok) {
          shareAlert.innerText = "Note shared successfully.";
          shareAlert.classList.remove("d-none", "text-danger");
          shareAlert.classList.add("text-success");
          shareEmail.value = "";
          fetchShares(currentNoteId);
        } else {
          shareAlert.innerText = data.error || "Failed to share";
          shareAlert.classList.remove("d-none", "text-success");
          shareAlert.classList.add("text-danger");
        }
      } catch (err) {
        shareAlert.innerText = "Connection Error";
        shareAlert.classList.remove("d-none", "text-success");
        shareAlert.classList.add("text-danger");
      }
      btn.innerText = "Share";
    });

  // Handle Shared with Me link in navigation
  let viewingMode = "all";
  document.getElementById("navAllNotes")?.addEventListener("click", (e) => {
    e.preventDefault();
    viewingMode = "all";
    document
      .querySelectorAll(".nav-link")
      .forEach((l) => l.classList.remove("active"));
    e.target.classList.add("active");
    fetchNotes();
    document.getElementById("dashboardTitle").innerText = "All Notes";
  });

  document.getElementById("navPins")?.addEventListener("click", (e) => {
    e.preventDefault();
    viewingMode = "pinned";
    document
      .querySelectorAll(".nav-link")
      .forEach((l) => l.classList.remove("active"));
    e.target.classList.add("active");
    filteredNotes = notes.filter((n) => n.is_pinned == 1);
    renderNotes(searchInput.value, filteredNotes);
    document.getElementById("dashboardTitle").innerText = "Pinned Notes";
  });

  document.getElementById("navShared")?.addEventListener("click", async (e) => {
    e.preventDefault();
    viewingMode = "shared";
    document
      .querySelectorAll(".nav-link")
      .forEach((l) => l.classList.remove("active"));
    e.target.classList.add("active");
    document.getElementById("dashboardTitle").innerText = "Shared With Me";

    try {
      const res = await fetch(`${rootUrl}/shares/read.php`, {
        headers: getApiHeaders(),
      });
      if (res.ok) {
        const data = await res.json();
        notes = data;
        renderNotes();
      }
    } catch (err) {
      console.error(err);
    }
  });

  const addLabelBtn = document.getElementById("addLabelBtn");
  if (addLabelBtn) {
    addLabelBtn.addEventListener("click", async (e) => {
      e.preventDefault();

      const labelName = prompt("Enter new label name:");
      if (!labelName || labelName.trim() === "") return;

      const colors = [
        "#0d6efd",
        "#6610f2",
        "#6f42c1",
        "#d63384",
        "#dc3545",
        "#fd7e14",
        "#198754",
        "#20c997",
        "#0dcaf0",
      ];
      const randomColor = colors[Math.floor(Math.random() * colors.length)];

      try {
        const res = await fetch(`${rootUrl}/labels/create.php`, {
          method: "POST",
          headers: getApiHeaders(),
          body: JSON.stringify({ name: labelName, color: randomColor }),
        });

        if (res.ok) {
          fetchLabels();
        } else {
          const data = await res.json();
          alert(data.error || "Failed to create label.");
        }
      } catch (err) {
        console.error(err);
        alert("Connection error.");
      }
    });
  }

  // Initialize App
  const initApp = () => {
    checkAuth();
  };

  initApp();
});
