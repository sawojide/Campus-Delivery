<!-- OneSignal Push Notifications -->
<script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async></script>
<script>
  window.OneSignal = window.OneSignal || [];
  OneSignal.push(function() {
    OneSignal.init({
      appId: "<?= ONESIGNAL_APP_ID ?>",
      notifyButton: {
        enable: true,
      },
      promptOptions: {
        slidedown: {
          enabled: true,
          types: [{"category": "push", "text": {"actionMessage": "We'd like to show you notifications for order updates!", "acceptButtonText": "Allow", "cancelButtonText": "Deny"}}]
        }
      }
    });
  });
</script>
