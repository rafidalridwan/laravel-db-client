function showToast(option) {
  const wrapper = $(option.eleWrapper || '#dbclient-toaster');
  const toast = createToast(option);
  
  // Initial styling for animation
  const $toast = $(toast).css({
    display: 'none',
    opacity: 0,
    transform: 'translateX(400px)',
    transition: 'all 0.3s ease'
  });

  // Append first, then trigger animation
  wrapper.append($toast);
  
  setTimeout(() => {
    $toast.css({
      display: 'block',
      opacity: 1,
      transform: 'translateX(0)'
    });
  }, 10);

  // Auto close
  if (option.autoClose !== false) {
    const outTime = option.autoCloseTime || 3500;
    if (outTime > 0) {
      const watch = setTimeout(() => {
        $toast.css({
          transform: 'translateX(400px)',
          opacity: 0
        });
        setTimeout(() => {
          $toast.remove();
          if (option.afterClose) option.afterClose();
        }, 300);
      }, outTime);
      
      // Store timeout ID for manual close
      $toast.data('timeout', watch);
    }
  }

  // Close button event (use event delegation to handle dynamically added buttons)
  $(document).off('click', '.hs-close').on('click', '.hs-close', function() {
    const $parentToast = $(this).closest('.hs-toast');
    clearTimeout($parentToast.data('timeout'));
    $parentToast.css({
      transform: 'translateX(400px)',
      opacity: 0
    });
    setTimeout(() => {
      $parentToast.remove();
      if (option.afterClose) option.afterClose();
    }, 300);
  });

  if (option.afterShow) {
    option.afterShow();
  }
  
  return $toast;
}

function createToast(option) {
  const final = toastCaseValidation(option);
  const closeBtn = option.closeButton !== false 
    ? '<div class="hs-toast-action"><button type="button" class="hs-close">×</button></div>' 
    : '';
  
  const html = `
    <div class="hs-toast hs-theme-${(option.theme || 'info').toLowerCase()}">
      <div class="hs-toast-inner">
        <div class="hs-toast-icons">
          ${final.icon}
        </div>
        <div class="hs-toast-msg">
          ${final.msg}
        </div>
        ${closeBtn}
      </div>
    </div>`;
  
  return html;
}

function toastCaseValidation(option) {
  const finalOption = {};
  let toastmsg;
  let themeIco;
  
  switch ((option.theme || 'info').toLowerCase()) {
    case 'error':
      themeIco = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#dc2626"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>';
      break;
    case 'success':
      themeIco = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#16a34a"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      break;
    case 'warning':
      themeIco = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="#fef3c7"/></svg>';
      break;
    default:
      themeIco = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#2563eb"/><path d="M12 8V12M12 16H12.01" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>';
  }

  if (option.msg == undefined || option.msg === '') {
    toastmsg = 'No message provided';
  } else {
    if (Array.isArray(option.msg)) {
      toastmsg = '<ul style="margin: 0; padding-left: 20px;">';
      option.msg.forEach(function(val) {
        toastmsg = toastmsg + '<li>' + escapeHtml(String(val)) + '</li>';
      });
      toastmsg = toastmsg + '</ul>';
    } else {
      toastmsg = escapeHtml(String(option.msg));
    }
  }
  
  finalOption.icon = themeIco;
  finalOption.msg = toastmsg;
  return finalOption;
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

