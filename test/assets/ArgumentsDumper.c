#include <stdio.h>

int main(int argc, char* argv[])
{
	int i;
	printf("%d\n", argc - 1);
	for (i = 1; i < argc; i++) {
		printf("#%d>>>%s<<<\n", i - 1, argv[i]);
	}
	return 0;
}
