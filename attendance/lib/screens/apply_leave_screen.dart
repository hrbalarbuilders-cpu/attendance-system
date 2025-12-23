import 'package:flutter/material.dart';
import '../services/leave_service.dart';

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
  String leaveDateType = 'single';

  @override
  void initState() {
    super.initState();
    fetchLeaveTypes();
  }

  Future<void> fetchLeaveTypes() async {
    setState(() { isLoading = true; errorMsg = null; });
    try {
      final types = await leaveService.fetchLeaveTypes(widget.employeeId);
      setState(() {
        leaveTypes = types.map<Map<String, dynamic>>((e) => Map<String, dynamic>.from(e)).toList();
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMsg = 'Error: $e';
        isLoading = false;
      });
    }
  }

  Future<void> _pickDate(BuildContext context, TextEditingController controller) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      controller.text = "${picked.day.toString().padLeft(2, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.year}";
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Apply Leave'),
        centerTitle: true,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMsg != null
              ? Center(child: Text(errorMsg!))
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
                                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 12),
                              ...leaveBalances.map((leave) => Padding(
                                    padding: const EdgeInsets.symmetric(vertical: 4),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          leave['type'],
                                          style: const TextStyle(fontSize: 16),
                                        ),
                                        Text(
                                          leave['balance'].toString(),
                                          style: const TextStyle(
                                              fontSize: 16, fontWeight: FontWeight.w500),
                                        ),
                                      ],
                                    ),
                                  )),
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
                                  child: DropdownButtonFormField<String>(
                                    decoration: const InputDecoration(
                                      labelText: 'Leave Type',
                                      border: OutlineInputBorder(),
                                      contentPadding:
                                          EdgeInsets.symmetric(horizontal: 12, vertical: 16),
                                    ),
                                    isExpanded: true,
                                    items: leaveTypes
                                        .map<DropdownMenuItem<String>>((type) => DropdownMenuItem<String>(
                                              value: type['name'] as String,
                                              child: Text(type['name']),
                                            ))
                                        .toList(),
                                    initialValue: selectedLeaveType,
                                    onChanged: (value) {
                                      setState(() {
                                        selectedLeaveType = value;
                                      });
                                    },
                                    hint: const Text('Select leave type'),
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
                                                if (sel) setState(() => leaveDateType = 'single');
                                              },
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: ChoiceChip(
                                              label: const Text('Multiple Leave'),
                                              selected: leaveDateType == 'multiple',
                                              onSelected: (sel) {
                                                if (sel) setState(() => leaveDateType = 'multiple');
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
                                          onTap: () => _pickDate(context, fromDateController),
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
                                          onTap: () => _pickDate(context, fromDateController),
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
                                          onTap: () => _pickDate(context, toDateController),
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
                                    onPressed: () async {
                                      final selectedType = leaveTypes.firstWhere(
                                        (type) => type['name'] == selectedLeaveType,
                                        orElse: () => <String, dynamic>{},
                                      );
                                      if (selectedType.isEmpty) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          const SnackBar(content: Text('Select a leave type')),
                                        );
                                        return;
                                      }
                                      final leaveTypeId = selectedType['id'];
                                      final fromDateStr = fromDateController.text;
                                      final toDateStr = leaveDateType == 'multiple' && toDateController.text.isNotEmpty
                                          ? toDateController.text
                                          : fromDateStr;
                                      DateTime? fromDateParsed;
                                      DateTime? toDateParsed;
                                      try {
                                        final fromParts = fromDateStr.split('-');
                                        final toParts = toDateStr.split('-');
                                        fromDateParsed = DateTime.parse('${fromParts[2]}-${fromParts[1].padLeft(2, '0')}-${fromParts[0].padLeft(2, '0')}');
                                        toDateParsed = DateTime.parse('${toParts[2]}-${toParts[1].padLeft(2, '0')}-${toParts[0].padLeft(2, '0')}');
                                      } catch (e) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(content: Text('Invalid date format.')),
                                        );
                                        return;
                                      }
                                      if (fromDateParsed.isAfter(toDateParsed)) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(content: Text('From date cannot be after To date.')),
                                        );
                                        return;
                                      }
                                      final fromDate = "${fromDateParsed.year}-${fromDateParsed.month.toString().padLeft(2, '0')}-${fromDateParsed.day.toString().padLeft(2, '0')}";
                                      final toDate = "${toDateParsed.year}-${toDateParsed.month.toString().padLeft(2, '0')}-${toDateParsed.day.toString().padLeft(2, '0')}";
                                      final reason = reasonController.text;
                                      final messenger = ScaffoldMessenger.of(context);
                                      try {
                                        final success = await leaveService.applyLeave({
                                          'employee_id': widget.employeeId.toString(),
                                          'leave_type_id': leaveTypeId.toString(),
                                          'from_date': fromDate,
                                          'to_date': toDate,
                                          'reason': reason,
                                        });
                                        messenger.showSnackBar(
                                          SnackBar(content: Text(success ? 'Leave applied successfully' : 'Failed to apply leave')),
                                        );
                                      } catch (e) {
                                        messenger.showSnackBar(
                                          SnackBar(content: Text(e.toString())),
                                        );
                                      }
                                    },
                                    child: const Text('Apply Leave'),
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
