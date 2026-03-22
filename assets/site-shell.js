(function () {
  const STYLE_ID = "member-shell-style";

  function injectStyle() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement("style");
    style.id = STYLE_ID;
    style.textContent = `
.top-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  max-width: none;
  z-index: 1004;
  border-top: 1px solid #d8e7f5;
  border-bottom: 1px solid #d8e7f5;
  border-left: 0;
  border-right: 0;
  border-radius: 0;
  padding: calc(env(safe-area-inset-top, 0px) + 14px) clamp(22px, 3.2vw, 38px) 14px clamp(10px, 1.4vw, 18px);
  background: #ffffff;
  box-shadow: none;
  backdrop-filter: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.nav-brand {
  font-family: "Space Grotesk", sans-serif;
  font-weight: 700;
  font-size: 1.08rem;
  letter-spacing: 0.01em;
  color: #1f3347;
  text-decoration: none;
  white-space: nowrap;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
  margin-left: auto;
}

.nav-toggle {
  display: block;
  position: relative;
  z-index: 1002;
  pointer-events: auto;
  background: transparent;
  color: #2e4760;
  border: 0;
  font-family: "Space Grotesk", sans-serif;
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  cursor: pointer;
  padding: 8px 0;
  margin-right: 10px;
  min-width: auto;
  height: auto;
  border-radius: 0;
}

.nav-toggle::after {
  content: "";
  display: block;
  margin-top: 3px;
  height: 2px;
  width: 100%;
  background: linear-gradient(90deg, #ff8a57, #36d5ff);
}

.nav-quick {
  margin-left: auto;
  gap: 8px;
  display: inline-flex;
  align-items: center;
}

.quick-btn,
.quick-btn:hover {
  text-decoration: none;
  color: #2b445b;
  border: 1px solid #d8e7f5;
  background: #f8fcff;
  padding: 6px 8px;
  border-radius: 10px;
  font-size: 0.76rem;
  font-weight: 700;
  line-height: 1;
}

.quick-btn-primary,
.quick-btn-primary:hover {
  background: linear-gradient(95deg, #ff8a57, #ff5f7f);
  color: #210f1a;
  border-color: transparent;
  box-shadow: none;
}

.nav-links {
  position: fixed;
  top: 0;
  right: 0;
  height: 100vh;
  width: min(340px, 82vw);
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: calc(env(safe-area-inset-top, 0px) + 78px) 20px 28px;
  border-left: 1px solid #d8e7f5;
  background: rgba(255, 255, 255, 0.98);
  transform: translateX(100%);
  transition: transform 0.28s ease;
  box-shadow: -22px 0 48px rgba(0, 0, 0, 0.4);
  z-index: 1001;
  align-items: stretch;
  justify-content: flex-start;
}

.top-nav.open .nav-links {
  transform: translateX(0);
}

.nav-link,
.nav-link:hover {
  min-height: 44px;
  width: 100%;
  border-radius: 12px;
  border: 1px solid #d8e7f5;
  background: #f8fcff;
  color: #2b445b;
  text-decoration: none;
  font-size: 0.92rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  padding: 0 14px;
  box-shadow: none;
  transform: none;
}

.nav-link.menu-cta,
.nav-link.menu-cta:hover {
  background: linear-gradient(95deg, #ff8a57, #ff5f7f);
  color: #210f1a;
  border-color: transparent;
}

.lang-switch-group {
  border-top: 1px solid #d8e7f5;
  margin-top: 6px;
  padding-top: 12px;
}

.lang-toggle-btn {
  min-height: 40px;
  border-radius: 12px;
  border: 1px solid #d8e7f5;
  background: #f8fcff;
  color: #2b445b;
  font: inherit;
  font-size: 0.86rem;
  font-weight: 700;
  width: 100%;
  cursor: pointer;
}

.lang-switch-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 10px;
}

.lang-switch-list a,
.lang-switch-list a:hover {
  min-height: 40px;
  border-radius: 12px;
  border: 1px solid #d8e7f5;
  background: #ffffff;
  color: #2b445b;
  font-size: 0.86rem;
  font-weight: 700;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  padding: 0 12px;
}

.site-footer {
  margin-top: 26px;
  border-top: 1px solid #d8e7f5;
  padding-top: 18px;
  color: #587089;
  text-align: center;
}

.footer-links {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}

.footer-link {
  color: #35506a;
  border: 1px solid #cfe2f2;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.82rem;
  background: #ffffff;
  text-decoration: none;
  display: inline-flex;
}

@media (max-width: 820px) {
  .top-nav {
    padding: calc(env(safe-area-inset-top, 0px) + 12px) 14px 12px;
  }

  .nav-brand {
    font-size: 1rem;
  }

  .nav-quick {
    display: inline-flex;
    gap: 6px;
  }
}
`;
    document.head.appendChild(style);
  }

  function getBaseHref() {
    const path = location.pathname || "/";
    const depth = path.split("/").filter(Boolean).length - 1;
    return depth > 0 ? "../".repeat(depth) : "./";
  }

  function buildNav(base) {
    return `
<nav id="memberTopNav" class="top-nav">
  <a class="nav-brand" href="${base}member.html?mode=login">DTZ-LiD</a>
  <div class="nav-right">
    <div class="nav-quick">
      <a class="quick-btn" href="${base}member.html?mode=login">Anmelden</a>
      <a class="quick-btn quick-btn-primary" href="${base}register.html">Registrieren</a>
    </div>
    <div class="nav-links" id="memberNavLinks">
      <a class="nav-link" href="${base}index.html">Kaffee</a>
      <a class="nav-link" href="${base}index.html">Katina</a>
      <a class="nav-link" href="${base}index.html">Tarot</a>
      <a class="nav-link" href="${base}member.html?mode=login">Anmelden</a>
      <a class="nav-link menu-cta" href="${base}register.html">Registrieren</a>
      <div class="lang-switch lang-switch-group" id="memberLangGroup">
        <button type="button" class="lang-toggle-btn" id="memberLangToggleBtn" aria-expanded="false">🌐 Sprachen</button>
        <div class="lang-switch-list" id="memberLangList" hidden>
          <a href="${base}member.html?mode=login&lang=tr">🇹🇷 Türkisch</a>
          <a href="${base}member.html?mode=login&lang=en">🇬🇧 Englisch</a>
          <a href="${base}member.html?mode=login&lang=de">🇩🇪 Deutsch</a>
        </div>
      </div>
    </div>
  </div>
  <button id="memberNavToggleBtn" class="nav-toggle" type="button" aria-label="Navigation öffnen">Menu</button>
</nav>`;
  }

  function buildFooter(base) {
    return `
<footer class="site-footer">
  <div class="footer-links">
    <a class="footer-link" href="${base}agb.html">AGB</a>
  </div>
</footer>`;
  }

  function mountShell() {
    injectStyle();
    const base = getBaseHref();
    const shell = document.querySelector(".page-shell") || document.body;

    const nav = document.querySelector(".top-nav");
    if (nav) {
      nav.outerHTML = buildNav(base);
    } else {
      shell.insertAdjacentHTML("afterbegin", buildNav(base));
    }

    const footer = document.querySelector(".site-footer");
    if (footer) {
      footer.outerHTML = buildFooter(base);
    } else {
      shell.insertAdjacentHTML("beforeend", buildFooter(base));
    }

    const topNav = document.getElementById("memberTopNav");
    const toggleBtn = document.getElementById("memberNavToggleBtn");
    const langBtn = document.getElementById("memberLangToggleBtn");
    const langList = document.getElementById("memberLangList");

    if (toggleBtn && topNav) {
      toggleBtn.addEventListener("click", () => {
        topNav.classList.toggle("open");
      });
    }

    if (langBtn && langList) {
      langBtn.addEventListener("click", () => {
        const nextHidden = !langList.hidden;
        langList.hidden = nextHidden;
        langBtn.setAttribute("aria-expanded", String(!nextHidden));
      });
    }

    document.addEventListener("click", (event) => {
      const target = event.target;
      if (!(target instanceof Node)) return;
      if (topNav && !topNav.contains(target)) {
        topNav.classList.remove("open");
      }
      if (langBtn && langList && !langBtn.contains(target) && !langList.contains(target)) {
        langList.hidden = true;
        langBtn.setAttribute("aria-expanded", "false");
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        if (topNav) topNav.classList.remove("open");
        if (langBtn && langList) {
          langList.hidden = true;
          langBtn.setAttribute("aria-expanded", "false");
        }
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountShell, { once: true });
  } else {
    mountShell();
  }
})();
