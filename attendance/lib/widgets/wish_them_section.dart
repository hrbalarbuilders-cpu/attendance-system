import 'package:flutter/material.dart';

/// Type of wish — controls badge style
enum WishType { birthday, anniversary, newJoin }

/// Simple model for wish users (birthday / anniversary / new joinee)
class WishUser {
  final String name;
  final String? photo;
  final String date;     // e.g. "01 Jan"
  final String? years;   // only used for anniversary
  final WishType type;

  WishUser({
    required this.name,
    this.photo,
    required this.date,
    this.years,
    required this.type,
  });
}

/// A horizontal list section that shows users to "Wish them".
class WishThemSection extends StatelessWidget {
  final List<WishUser> users;
  final double cardWidth;

  const WishThemSection({
    super.key,
    required this.users,
    this.cardWidth = 100,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: const [
          BoxShadow(
            color: Color.fromRGBO(0, 0, 0, 0.05),
            blurRadius: 12,
            offset: Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Wish them',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
          ),
          const SizedBox(height: 12),

          if (users.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Text(
                'No wishes today',
                style: TextStyle(color: Colors.black54),
              ),
            )
          else
            SizedBox(
              height: 150,
              child: ListView.separated(
                padding: const EdgeInsets.only(right: 4),
                scrollDirection: Axis.horizontal,
                itemCount: users.length,
                separatorBuilder: (ctx, idx) => const SizedBox(width: 10),
                itemBuilder: (context, i) =>
                    SizedBox(width: cardWidth, child: WishCard(user: users[i])),
              ),
            ),
        ],
      ),
    );
  }
}

class WishCard extends StatelessWidget {
  final WishUser user;

  const WishCard({super.key, required this.user});

  String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty) return '';
    return parts.take(2).map((p) => p[0]).join().toUpperCase();
  }

  /// Decide badge look based on type
  Map<String, dynamic> _badgeStyle() {
    switch (user.type) {
      case WishType.birthday:
        return {
          "color": const Color(0xFFB8AFFF), // lavender
          "text": "BIRTHDAY",
        };

      case WishType.anniversary:
        return {
          "color": const Color(0xFF5BA7FF), // blue
          "text": "${user.years ?? "1"} YRS",
        };

      case WishType.newJoin:
        return {
          "color": const Color(0xFF4CAF50), // green
          "text": "NEW",
        };
    }
  }

  @override
  Widget build(BuildContext context) {
    const bg = Color(0xFFF3F4FF);
    final badge = _badgeStyle();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Stack(
            clipBehavior: Clip.none,
            alignment: Alignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: const BoxDecoration(
                  color: bg,
                  shape: BoxShape.circle,
                ),
                child: CircleAvatar(
                  radius: 22,
                  backgroundColor: bg,
                  foregroundImage:
                      user.photo != null ? NetworkImage(user.photo!) : null,
                  child: user.photo == null
                      ? Text(
                          _initials(user.name),
                          style: const TextStyle(
                            color: Color(0xFF7B8CFF),
                            fontWeight: FontWeight.bold,
                          ),
                        )
                      : null,
                ),
              ),

              // Badge under avatar
              Positioned(
                bottom: -10,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: badge["color"],
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Text(
                    badge["text"],
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 20),

          Text(
            user.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            user.date,
            style: const TextStyle(
              fontSize: 11,
              color: Colors.black54,
            ),
          ),
        ],
      ),
    );
  }
}
