class Resource {
  final String uid;
  final String title;
  final String? description;
  final String? fileName;
  final String? downloadUrl;

  Resource({
    required this.uid,
    required this.title,
    this.description,
    this.fileName,
    this.downloadUrl,
  });

  factory Resource.fromJson(Map<String, dynamic> json) {
    return Resource(
      uid: json['uid'] ?? '',
      title: json['title'] ?? '',
      description: json['description'],
      fileName: json['file_name'],
      downloadUrl: json['download_url'],
    );
  }
}
