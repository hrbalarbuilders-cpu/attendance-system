import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:async';
import '../services/leave_service.dart';
import '../widgets/bottom_banner.dart';

class ApplyLeaveScreen extends StatefulWidget {
  final int employeeId;
  const ApplyLeaveScreen({super.key, required this.employeeId});

  @override
  State<ApplyLeaveScreen> createState() => _ApplyLeaveScreenState();
}

class _ApplyLeaveScreenState extends State<ApplyLeaveScreen> {
  final LeaveService leaveService = LeaveService();
  List<Map<String, dynamic>> leaveTypes = [];
  List<Map<String, dynamic>> leaveBalances = [
    {'type': 'Casual Leave', 'balance': 5},
    {'type': 'Sick Leave', 'balance': 8},
    {'type': 'Earned Leave', 'balance': 12},
  ];
  String? selectedLeaveType;
  final _formKey = GlobalKey<FormState>();
  final TextEditingController fromDateController = TextEditingController();
  final TextEditingController toDateController = TextEditingController();
  final TextEditingController reasonController = TextEditingController();
  bool isLoading = true;
  String? errorMsg;
  String? debugInfo;
  String leaveDateType = 'single';
  bool _isOffline = false;
  late StreamSubscription<List<ConnectivityResult>> _connectivitySubscription;

  @override
  void initState() {
    super.initState();
    _checkInitialConnectivity();
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen(
      _updateConnectionStatus,
    );
    fetchLeaveTypes();
  }

  Future<void> _checkInitialConnectivity() async {
    final List<ConnectivityResult> results = await Connectivity()
        .checkConnectivity();
    _updateConnectionStatus(results);
  }

  void _updateConnectionStatus(List<ConnectivityResult> results) {
    if (mounted) {
      setState(() {
        _isOffline = results.contains(ConnectivityResult.none);
      });
      if (!_isOffline && errorMsg != null) {
        // Re-fetch data if connection restored and we were in error state
        fetchLeaveTypes();
      }
    }
  }

  @override
  void dispose() {
    _connectivitySubscription.cancel();
    fromDateController.dispose();
    toDateController.dispose();
    reasonController.dispose();
    super.dispose();
  }

  Future<void> fetchLeaveTypes() async {
    setState(() {
      isLoading = true;
      errorMsg = null;
    });
    if (widget.employeeId <= 0) {
      setState(() {
        errorMsg = 'Invalid employee id';
        isLoading = false;
      });
      return;
    }
    try {
      final types = await leaveService.fetchLeaveTypes(widget.employeeId);
      final parsedTypes = types
          .map<Map<String, dynamic>>((e) => Map<String, dynamic>.from(e))
          .toList();
      // Also fetch balances for these leave types
      List<Map<String, dynamic>> balances = [];
      try {
        final balRes = await leaveService.fetchLeaveBalances(widget.employeeId);
        balances = balRes
            .map<Map<String, dynamic>>((b) => Map<String, dynamic>.from(b))
            .toList();
      } catch (_) {
        // ignore balance fetch errors; we'll show types without balances
        balances = [];
      }
      setState(() {
        leaveTypes = parsedTypes;
        // map balances into the simple representation expected by the UI
        if (balances.isNotEmpty) {
          leaveBalances = balances
              .map(
                (b) => {
                  'type': b['name'] ?? b['code'] ?? 'Unknown',
                  'total': b['yearly_quota'] ?? 0,
                  'used': b['used'] ?? 0,
                  'available': b['available'] ?? 0,
                },
              )
              .toList();
        } else {
          // fallback: if no balances returned, keep existing hardcoded or empty values
          if (leaveBalances.isEmpty) {
            leaveBalances = parsedTypes
                .map(
                  (t) => {
                    'type': t['name'] ?? 'Unknown',
                    'total': 0,
                    'available': 0,
                    'used': 0,
                  },
                )
                .toList();
          }
        }
        isLoading = false;
        debugInfo = null;
      });
    } catch (e) {
      setState(() {
        errorMsg = 'Error: $e';
        // capture debug-friendly info for troubleshooting (may include HTTP status and body)
        debugInfo = e.toString();
        isLoading = false;
      });
    }
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController controller,
  ) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      controller.text =
          "${picked.day.toString().padLeft(2, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.year}";
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Apply Leave'), centerTitle: true),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMsg != null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8.0),
                      child: Text(
                        'Employee ID: ${widget.employeeId}',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    ),
                    Text(errorMsg!, textAlign: TextAlign.center),
                    const SizedBox(height: 12),
                    if (debugInfo != null)
                      SelectableText(
                        debugInfo!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 12,
                          color: Colors.black54,
                        ),
                      ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        ElevatedButton(
                          onPressed: _isOffline ? null : fetchLeaveTypes,
                          child: Text(_isOffline ? 'Offline' : 'Retry'),
                        ),
                        const SizedBox(width: 8),
                        if (debugInfo != null)
                          ElevatedButton(
                            onPressed: () {
                              Clipboard.setData(
                                ClipboardData(text: debugInfo ?? ''),
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Copied debug info'),
                                ),
                              );
                            },
                            child: const Text('Copy'),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            )
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Card(
                    elevation: 2,
                    margin: const EdgeInsets.only(bottom: 20),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Leave Balance',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 12),
                          ...leaveBalances.map(
                            (leave) => Padding(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        'Available: ${leave['available']?.toString() ?? '0'}',
                                        style: const TextStyle(
                                          fontSize: 18,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      Text(
                                        'Total: ${leave['total']?.toString() ?? '0'}',
                                        style: const TextStyle(
                                          fontSize: 14,
                                          color: Colors.grey,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    leave['type'],
                                    style: const TextStyle(
                                      fontSize: 14,
                                      color: Colors.black87,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  Card(
                    elevation: 2,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          children: [
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              child: Builder(
                                builder: (ctx) {
                                  final GlobalKey fieldKey = GlobalKey();
                                  Future<void> showLeaveMenu() async {
                                    final RenderBox box =
                                        fieldKey.currentContext
                                                ?.findRenderObject()
                                            as RenderBox;
                                    final Offset pos = box.localToGlobal(
                                      Offset.zero,
                                    );
                                    final Size size = box.size;
                                    final screenSize = MediaQuery.of(
                                      context,
                                    ).size;
                                    final RelativeRect position =
                                        RelativeRect.fromLTRB(
                                          pos.dx,
                                          pos.dy + size.height,
                                          screenSize.width -
                                              (pos.dx + size.width),
                                          screenSize.height -
                                              (pos.dy + size.height),
                                        );

                                    final selection = await showMenu<String?>(
                                      context: ctx,
                                      position: position,
                                      items: leaveTypes.map<PopupMenuEntry<String>>((
                                        type,
                                      ) {
                                        final name =
                                            type['name'] as String? ?? '';
                                        final balEntry = leaveBalances
                                            .firstWhere(
                                              (b) =>
                                                  (b['type'] as String? ??
                                                      '') ==
                                                  name,
                                              orElse: () => {'available': 0},
                                            );
                                        final bal = balEntry['available'] ?? 0;
                                        return PopupMenuItem<String>(
                                          value: name,
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(
                                              vertical: 8,
                                              horizontal: 4,
                                            ),
                                            child: Row(
                                              mainAxisAlignment:
                                                  MainAxisAlignment
                                                      .spaceBetween,
                                              children: [
                                                Text(
                                                  name,
                                                  style: const TextStyle(
                                                    fontSize: 15,
                                                  ),
                                                ),
                                                Container(
                                                  padding:
                                                      const EdgeInsets.symmetric(
                                                        horizontal: 8,
                                                        vertical: 4,
                                                      ),
                                                  decoration: BoxDecoration(
                                                    color: Colors.grey.shade100,
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                          6,
                                                        ),
                                                  ),
                                                  child: Text(
                                                    'Available: ${bal.toString()}',
                                                    style: const TextStyle(
                                                      fontSize: 13,
                                                      color: Colors.grey,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        );
                                      }).toList(),
                                    );
                                    if (selection != null) {
                                      setState(() {
                                        selectedLeaveType = selection;
                                      });
                                    }
                                  }

                                  return Container(
                                    key: fieldKey,
                                    decoration: BoxDecoration(
                                      border: Border.all(
                                        color: Colors.grey.shade400,
                                      ),
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: ListTile(
                                      contentPadding:
                                          const EdgeInsets.symmetric(
                                            horizontal: 12,
                                          ),
                                      title: Text(
                                        selectedLeaveType ??
                                            'Select leave type',
                                        style: const TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                      trailing: Icon(
                                        Icons.arrow_drop_down_circle,
                                        color: Theme.of(context).primaryColor,
                                      ),
                                      onTap: showLeaveMenu,
                                    ),
                                  );
                                },
                              ),
                            ),
                            const SizedBox(height: 16),
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Use ChoiceChips instead of RadioListTile (avoids deprecated Radio groupValue API)
                                  Row(
                                    children: [
                                      Expanded(
                                        child: ChoiceChip(
                                          label: const Text('Single Leave'),
                                          selected: leaveDateType == 'single',
                                          onSelected: (sel) {
                                            if (sel)
                                              setState(
                                                () => leaveDateType = 'single',
                                              );
                                          },
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: ChoiceChip(
                                          label: const Text('Multiple Leave'),
                                          selected: leaveDateType == 'multiple',
                                          onSelected: (sel) {
                                            if (sel)
                                              setState(
                                                () =>
                                                    leaveDateType = 'multiple',
                                              );
                                          },
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  if (leaveDateType == 'single')
                                    TextFormField(
                                      controller: fromDateController,
                                      readOnly: true,
                                      decoration: const InputDecoration(
                                        labelText: 'Date',
                                        border: OutlineInputBorder(),
                                        suffixIcon: Icon(Icons.calendar_today),
                                      ),
                                      onTap: () => _pickDate(
                                        context,
                                        fromDateController,
                                      ),
                                    )
                                  else ...[
                                    TextFormField(
                                      controller: fromDateController,
                                      readOnly: true,
                                      decoration: const InputDecoration(
                                        labelText: 'From Date',
                                        border: OutlineInputBorder(),
                                        suffixIcon: Icon(Icons.calendar_today),
                                      ),
                                      onTap: () => _pickDate(
                                        context,
                                        fromDateController,
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextFormField(
                                      controller: toDateController,
                                      readOnly: true,
                                      decoration: const InputDecoration(
                                        labelText: 'To Date',
                                        border: OutlineInputBorder(),
                                        suffixIcon: Icon(Icons.calendar_today),
                                      ),
                                      onTap: () =>
                                          _pickDate(context, toDateController),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),
                            TextFormField(
                              controller: reasonController,
                              maxLines: 3,
                              decoration: const InputDecoration(
                                labelText: 'Reason',
                                border: OutlineInputBorder(),
                                alignLabelWithHint: true,
                              ),
                            ),
                            const SizedBox(height: 24),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton(
                                onPressed: (isLoading || _isOffline)
                                    ? null
                                    : () async {
                                        final selectedType = leaveTypes
                                            .firstWhere(
                                              (type) =>
                                                  type['name'] ==
                                                  selectedLeaveType,
                                              orElse: () => <String, dynamic>{},
                                            );
                                        if (selectedType.isEmpty) {
                                          ScaffoldMessenger.of(
                                            context,
                                          ).showSnackBar(
                                            const SnackBar(
                                              content: Text(
                                                'Select a leave type',
                                              ),
                                            ),
                                          );
                                          return;
                                        }
                                        final leaveTypeId = selectedType['id'];
                                        final fromDateStr =
                                            fromDateController.text;
                                        final toDateStr =
                                            leaveDateType == 'multiple' &&
                                                toDateController.text.isNotEmpty
                                            ? toDateController.text
                                            : fromDateStr;
                                        DateTime? fromDateParsed;
                                        DateTime? toDateParsed;
                                        try {
                                          final fromParts = fromDateStr.split(
                                            '-',
                                          );
                                          final toParts = toDateStr.split('-');
                                          fromDateParsed = DateTime.parse(
                                            '${fromParts[2]}-${fromParts[1].padLeft(2, '0')}-${fromParts[0].padLeft(2, '0')}',
                                          );
                                          toDateParsed = DateTime.parse(
                                            '${toParts[2]}-${toParts[1].padLeft(2, '0')}-${toParts[0].padLeft(2, '0')}',
                                          );
                                        } catch (e) {
                                          ScaffoldMessenger.of(
                                            context,
                                          ).showSnackBar(
                                            SnackBar(
                                              content: Text(
                                                'Invalid date format.',
                                              ),
                                            ),
                                          );
                                          return;
                                        }
                                        if (fromDateParsed.isAfter(
                                          toDateParsed,
                                        )) {
                                          ScaffoldMessenger.of(
                                            context,
                                          ).showSnackBar(
                                            SnackBar(
                                              content: Text(
                                                'From date cannot be after To date.',
                                              ),
                                            ),
                                          );
                                          return;
                                        }
                                        final fromDate =
                                            "${fromDateParsed.year}-${fromDateParsed.month.toString().padLeft(2, '0')}-${fromDateParsed.day.toString().padLeft(2, '0')}";
                                        final toDate =
                                            "${toDateParsed.year}-${toDateParsed.month.toString().padLeft(2, '0')}-${toDateParsed.day.toString().padLeft(2, '0')}";
                                        final reason = reasonController.text;
                                        final messenger = ScaffoldMessenger.of(
                                          context,
                                        );
                                        try {
                                          final res = await leaveService
                                              .applyLeave({
                                                'user_id': widget.employeeId
                                                    .toString(),
                                                'leave_type_id': leaveTypeId
                                                    .toString(),
                                                'from_date': fromDate,
                                                'to_date': toDate,
                                                'reason': reason,
                                              });
                                          final ok = res['success'] == true;
                                          final msg = (res['message'] ?? '')
                                              .toString();
                                          messenger.showSnackBar(
                                            SnackBar(
                                              content: Text(
                                                ok
                                                    ? (msg.isNotEmpty
                                                          ? msg
                                                          : 'Leave applied successfully')
                                                    : (msg.isNotEmpty
                                                          ? msg
                                                          : 'Failed to apply leave'),
                                              ),
                                            ),
                                          );
                                        } catch (e) {
                                          messenger.showSnackBar(
                                            SnackBar(
                                              content: Text(e.toString()),
                                            ),
                                          );
                                        }
                                      },
                                child: Text(
                                  _isOffline ? 'Offline' : 'Apply Leave',
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
