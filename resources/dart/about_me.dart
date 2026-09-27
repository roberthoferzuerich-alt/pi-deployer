// about_me.dart
// Raspberry Pi 5 Laravel Deployer & Migrator - About Me & Team Screen

import 'package:flutter/material.dart';

class AboutMeScreen extends StatelessWidget {
  const AboutMeScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0E17),
      appBar: AppBar(
        title: const Text('Über Mich & Team'),
        backgroundColor: const Color(0xFF131B2E),
        elevation: 0,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            _buildProfileCard(
              initials: 'RH',
              name: 'Robert Hofer',
              role: 'Developer & System Architect',
              location: 'Zürich, Schweiz',
              email: 'robert.hofer.zuerich@bluewin.ch',
              badgeColor: const Color(0xFF8B5CF6),
            ),
            const SizedBox(height: 16),
            _buildProfileCard(
              initials: 'AI',
              name: 'Antigravity AI',
              role: 'AI Pair Programmer & Co-Pilot',
              location: 'Google DeepMind Team',
              email: 'Advanced Agentic Coding',
              badgeColor: const Color(0xFF10B981),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProfileCard({
    required String initials,
    required String name,
    required String role,
    required String location,
    required String email,
    required Color badgeColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF131B2E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: badgeColor.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: badgeColor,
            child: Text(
              initials,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                Text(role, style: TextStyle(color: badgeColor, fontSize: 12, fontWeight: FontWeight.w500)),
                const SizedBox(height: 6),
                Text('📍 $location', style: const TextStyle(color: Colors.white60, fontSize: 12)),
                Text('✉️ $email', style: const TextStyle(color: Colors.white70, fontSize: 12)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
