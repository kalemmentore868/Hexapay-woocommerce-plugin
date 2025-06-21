document.addEventListener("DOMContentLoaded", function () {
  if (
    window.wc?.wcBlocksRegistry?.registerPaymentMethod &&
    window.wp?.element &&
    window.wc?.wcSettings?.getSetting
  ) {
    const settings = window.wc.wcSettings.getSetting("hexakode_data", {});
    const { createElement } = window.wp.element;

    window.wc.wcBlocksRegistry.registerPaymentMethod({
      name: "hexakode",
      label: createElement("span", null, settings.title || "Hexakode Payments"),
      ariaLabel: settings.ariaLabel || "Hexakode Payments",
      supports: {
        features: ["products", "subscriptions", "default", "virtual"],
      },
      canMakePayment: () => Promise.resolve(true), // ✅ MUST RETURN AN OBJECT
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

    console.log("[Hexakode] Payment method registered for Blocks", settings);
  }
});
