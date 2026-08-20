import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/theme_service.dart';
import 'agent_detail_screen.dart';

class AgentsScreen extends StatefulWidget {
  const AgentsScreen({super.key});

  @override
  State<AgentsScreen> createState() => _AgentsScreenState();
}

class _AgentsScreenState extends State<AgentsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _agents = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadAgents();
  }

  Future<void> _loadAgents() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    final agents = await ApiService().fetchAgents();
    if (!mounted) return;
    if (agents != null) {
      setState(() {
        _agents = agents;
        _isLoading = false;
      });
    } else {
      setState(() {
        _error = 'Erreur lors du chargement des agents.';
        _isLoading = false;
      });
    }
  }

  void _openDetail(Map<String, dynamic> agent) {
    Navigator.of(context)
        .push(
      MaterialPageRoute(
        builder: (_) => AgentDetailScreen(
          agentUid: agent['_uid'],
          isCurrentUser: agent['is_current_user'] == true,
        ),
      ),
    )
        .then((changed) {
      if (changed == true) _loadAgents();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ThemeService().isDark;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Agents', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_error!, style: const TextStyle(color: Colors.red)),
                      const SizedBox(height: 12),
                      ElevatedButton(
                        onPressed: _loadAgents,
                        child: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : _agents.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.groups_rounded,
                              size: 64, color: Colors.grey.withValues(alpha: 0.4)),
                          const SizedBox(height: 16),
                          Text('Aucun agent pour le moment',
                              style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                          const SizedBox(height: 6),
                          Text(
                            'Ajoutez des membres d\'équipe depuis le tableau de bord web.',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadAgents,
                      child: ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                        itemCount: _agents.length,
                        itemBuilder: (context, index) {
                          final agent = _agents[index];
                          final bool isActive = agent['status'] == 1;
                          final String fullName =
                              '${agent['first_name'] ?? ''} ${agent['last_name'] ?? ''}'.trim();
                          final bool isSelf = agent['is_current_user'] == true;

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF1E293B) : Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade100),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.04),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                )
                              ],
                            ),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(16),
                              onTap: () => _openDetail(agent),
                              child: Padding(
                                padding: const EdgeInsets.all(14),
                                child: Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(10),
                                      decoration: BoxDecoration(
                                        color: (isActive ? ThemeService.primaryColor : Colors.grey)
                                            .withValues(alpha: 0.1),
                                        shape: BoxShape.circle,
                                      ),
                                      child: Icon(Icons.person_rounded,
                                          color: isActive ? ThemeService.primaryColor : Colors.grey,
                                          size: 24),
                                    ),
                                    const SizedBox(width: 14),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Flexible(
                                                child: Text(
                                                  fullName.isEmpty ? (agent['username'] ?? '') : fullName,
                                                  style: const TextStyle(
                                                      fontSize: 15, fontWeight: FontWeight.bold),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                              if (isSelf) ...[
                                                const SizedBox(width: 6),
                                                Text('(vous)',
                                                    style: TextStyle(
                                                        fontSize: 11, color: Colors.grey.shade500)),
                                              ],
                                            ],
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            agent['email'] ?? '',
                                            style: TextStyle(
                                                fontSize: 12.5,
                                                color: isDark ? Colors.white54 : Colors.black54),
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                          const SizedBox(height: 6),
                                          Row(
                                            children: [
                                              if ((agent['role_title'] ?? '').toString().isNotEmpty) ...[
                                                Container(
                                                  padding: const EdgeInsets.symmetric(
                                                      horizontal: 8, vertical: 3),
                                                  decoration: BoxDecoration(
                                                    color: ThemeService.primaryColor.withValues(alpha: 0.1),
                                                    borderRadius: BorderRadius.circular(6),
                                                  ),
                                                  child: Text(
                                                    agent['role_title'].toString(),
                                                    style: TextStyle(
                                                        fontSize: 10,
                                                        fontWeight: FontWeight.bold,
                                                        color: ThemeService.primaryColor),
                                                  ),
                                                ),
                                                const SizedBox(width: 6),
                                              ],
                                              Icon(Icons.event_rounded,
                                                  size: 11, color: Colors.grey.shade400),
                                              const SizedBox(width: 3),
                                              Expanded(
                                                child: Text(
                                                  agent['created_at'] ?? '',
                                                  style: TextStyle(
                                                      fontSize: 10.5, color: Colors.grey.shade500),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Column(
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: (isActive ? const Color(0xFF10B981) : const Color(0xFFEF4444))
                                                .withValues(alpha: 0.12),
                                            borderRadius: BorderRadius.circular(20),
                                          ),
                                          child: Text(
                                            agent['status_label'] ?? (isActive ? 'Actif' : 'Bloqué'),
                                            style: TextStyle(
                                                fontSize: 10.5,
                                                fontWeight: FontWeight.w700,
                                                color: isActive
                                                    ? const Color(0xFF10B981)
                                                    : const Color(0xFFEF4444)),
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        Icon(Icons.chevron_right, color: Colors.grey.shade400, size: 20),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}
