(function () {
  const interval = setInterval(() => {
    if (
      window.wc?.wcBlocksRegistry &&
      window.wc?.wcSettings &&
      window.wp?.element
    ) {
      clearInterval(interval);

      const settings =
        window.wc.wcSettings.getSetting("hexakode_data", {}) || {};

      const { createElement } = window.wp.element;

      console.log("[Hexakode5] Block script loaded", settings);

      window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: "hexakode",
        label: createElement(
          "span",
          null,
          settings.title || "Hexakode Payments"
        ),
        ariaLabel: settings.ariaLabel || "Hexakode Payments",
        supports: { features: ["products"] },
        canMakePayment: () => true,

        // ✅ React elements here, not functions returning them
        content: createElement(
          "p",
          null,
          settings.description || "Pay with Hexakode"
        ),
        edit: createElement(
          "p",
          null,
          settings.description || "Pay with Hexakode"
        ),

        save: null,
      });
    }
  }, 100);
})();
