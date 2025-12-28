import 'package:flutter/material.dart';

class BottomBanner {
  static OverlayEntry? _currentEntry;

  static void show(BuildContext context, String message, {bool success = true, Duration duration = const Duration(seconds: 3)}) {
    try {
      _currentEntry?.remove();
    } catch (_) {}

    final overlay = Overlay.of(context);

    final entry = OverlayEntry(
      builder: (ctx) => Positioned(
        left: 0,
        right: 0,
        bottom: 0,
        child: SafeArea(
          top: false,
          bottom: true,
          child: Material(
            color: Colors.transparent,
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
              decoration: const BoxDecoration(color: Colors.black),
              child: Row(
                mainAxisSize: MainAxisSize.max,
                children: [
                  Icon(
                    success ? Icons.check_circle : Icons.error,
                    color: const Color.fromRGBO(9, 230, 20, 1),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      message,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Color.fromRGBO(252, 255, 252, 1), fontSize: 14),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    _currentEntry = entry;
    overlay.insert(entry);

    Future.delayed(duration, () {
      if (_currentEntry == entry) {
        try {
          entry.remove();
        } catch (_) {}
        _currentEntry = null;
      }
    });
  }

  static void dismiss() {
    try {
      _currentEntry?.remove();
    } catch (_) {}
    _currentEntry = null;
  }
}
